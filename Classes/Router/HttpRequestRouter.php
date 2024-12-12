<?php

declare(strict_types=1);

namespace Pixelant\Interest\Router;

use Pixelant\Interest\Authentication\HttpBackendUserAuthentication;
use Pixelant\Interest\DataHandling\Operation\Exception\AbstractException;
use Pixelant\Interest\RequestHandler\AuthenticateRequestHandler;
use Pixelant\Interest\RequestHandler\CreateOrUpdateRequestHandler;
use Pixelant\Interest\RequestHandler\CreateRequestHandler;
use Pixelant\Interest\RequestHandler\DeleteRequestHandler;
use Pixelant\Interest\RequestHandler\Exception\AbstractRequestHandlerException;
use Pixelant\Interest\RequestHandler\ExceptionConverter\OperationToRequestHandlerExceptionConverter;
use Pixelant\Interest\RequestHandler\UpdateRequestHandler;
use Pixelant\Interest\Router\Event\HttpRequestRouterHandleByEvent;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;
use TYPO3\CMS\Core\Configuration\ExtensionConfiguration;
use TYPO3\CMS\Core\Context\Context;
use TYPO3\CMS\Core\Core\Bootstrap;
use TYPO3\CMS\Core\Core\Environment;
use TYPO3\CMS\Core\Domain\Repository\PageRepository;
use TYPO3\CMS\Core\EventDispatcher\EventDispatcher;
use TYPO3\CMS\Core\Http\JsonResponse;
use TYPO3\CMS\Core\Localization\LanguageServiceFactory;
use TYPO3\CMS\Core\Routing\PageArguments;
use TYPO3\CMS\Core\Site\Entity\Site;
use TYPO3\CMS\Core\Utility\GeneralUtility;
use TYPO3\CMS\Frontend\Authentication\FrontendUserAuthentication;
use TYPO3\CMS\Frontend\Controller\TypoScriptFrontendController;

/**
 * Routes requests to the correct handler and converts exceptions to responses.
 */
class HttpRequestRouter
{
    /**
     * Route the request to correct handler.
     *
     * @return ResponseInterface
     * @throws \Throwable
     */
    public static function route(ServerRequestInterface $request): ResponseInterface
    {
        self::initialize($request);

        $extensionConfiguration = GeneralUtility::makeInstance(ExtensionConfiguration::class);

        $entryPoint = substr(
            $request->getRequestTarget(),
            strlen(
                '/' . trim($extensionConfiguration->get('interest', 'entryPoint'), '/') . '/'
            )
        );

        if ($entryPoint === '') {
            $entryPointParts = [];
        } else {
            $entryPointParts = explode(
                '/',
                $entryPoint
            );
        }

        try {
            if (($entryPointParts[0] ?? null) === 'authenticate') {
                return GeneralUtility::makeInstance(
                    AuthenticateRequestHandler::class,
                    $entryPointParts,
                    $request
                )->handle();
            }

            return self::handleByMethod($request, $entryPointParts);
        } catch (AbstractRequestHandlerException $requestHandlerException) {
            return GeneralUtility::makeInstance(
                JsonResponse::class,
                [
                    'success' => false,
                    'message' => $requestHandlerException->getMessage(),
                ],
                $requestHandlerException->getCode()
            );
        } catch (\Throwable $throwable) {
            $trace = [];

            if (Environment::getContext()->isDevelopment()) {
                $trace = self::generateExceptionTrace($throwable);
            }

            return GeneralUtility::makeInstance(
                JsonResponse::class,
                [
                    'success' => false,
                    'message' => 'An exception occurred: ' . $throwable->getMessage(),
                    'trace' => $trace,
                ],
                500
            );
        }
    }



    /**
     * Necessary initialization.
     */
    protected static function initialize(ServerRequestInterface $request)
    {
        Bootstrap::initializeBackendUser(HttpBackendUserAuthentication::class, $request);

        self::bootFrontendController($request);

        Bootstrap::loadExtTables();

        $GLOBALS['LANG'] = $GLOBALS['LANG']
            ?? GeneralUtility::makeInstance(LanguageServiceFactory::class)->create('default');
    }

    /**
     * @param \Throwable $throwable
     * @return array
     */
    protected static function generateExceptionTrace(\Throwable $throwable): array
    {
        $trace = [];

        $currentThrowable = $throwable;

        do {
            $trace = array_merge(
                $trace,
                [
                    $currentThrowable->getMessage() => array_merge([
                        [
                            'file' => $currentThrowable->getFile(),
                            'line' => $currentThrowable->getLine(),
                        ],
                        $throwable->getTrace(),
                    ]),
                ]
            );

            $currentThrowable = $throwable->getPrevious();
        } while ($currentThrowable);

        return $trace;
    }

    /**
     * Handle a request depending on REST-compatible HTTP method.
     *
     * @param ServerRequestInterface $request
     * @param array $entryPointParts
     * @return ResponseInterface
     * @internal
     * phpcs:disable Squiz.Commenting.FunctionCommentThrowTag
     */
    public static function handleByMethod(ServerRequestInterface $request, array $entryPointParts): ResponseInterface
    {
        $event = GeneralUtility::makeInstance(EventDispatcher::class)->dispatch(
            new HttpRequestRouterHandleByEvent($request, $entryPointParts)
        );

        try {
            switch (strtoupper($event->getRequest()->getMethod())) {
                case 'POST':
                    return GeneralUtility::makeInstance(
                        CreateRequestHandler::class,
                        $event->getEntryPointParts(),
                        $event->getRequest()
                    )->handle();
                case 'PUT':
                    return GeneralUtility::makeInstance(
                        UpdateRequestHandler::class,
                        $event->getEntryPointParts(),
                        $event->getRequest()
                    )->handle();
                case 'PATCH':
                    return GeneralUtility::makeInstance(
                        CreateOrUpdateRequestHandler::class,
                        $event->getEntryPointParts(),
                        $event->getRequest()
                    )->handle();
                case 'DELETE':
                    return GeneralUtility::makeInstance(
                        DeleteRequestHandler::class,
                        $event->getEntryPointParts(),
                        $event->getRequest()
                    )->handle();
            }
        } catch (AbstractException $dataHandlingException) {
            throw OperationToRequestHandlerExceptionConverter::convert($dataHandlingException, $request);
        }

        return GeneralUtility::makeInstance(
            JsonResponse::class,
            [
                'success' => false,
                'message' => 'Method not allowed.',
            ],
            405
        );
    }

    /**
     * Booting up TSFE to make TSFE->sys_page available for ResourceFactory.
     *
     * @param ServerRequestInterface $request
     */
    protected static function bootFrontendController(ServerRequestInterface $request): void
    {
        /** @var Site $site */
        $site = $request->getAttribute('site', null);
        $frontendUser = GeneralUtility::makeInstance(FrontendUserAuthentication::class);
        $controller = GeneralUtility::makeInstance(
            TypoScriptFrontendController::class,
            GeneralUtility::makeInstance(Context::class),
            $site,
            $site->getDefaultLanguage(),
            new PageArguments($site->getRootPageId(), '0', []),
            $frontendUser
        );
        if (!isset($GLOBALS['TSFE']) || !$GLOBALS['TSFE'] instanceof TypoScriptFrontendController) {
            $GLOBALS['TSFE'] = $controller;
        }
        // @extensionScannerIgnoreLine
        if (!$GLOBALS['TSFE']->sys_page instanceof PageRepository) {
            // @extensionScannerIgnoreLine
            $GLOBALS['TSFE']->sys_page = GeneralUtility::makeInstance(PageRepository::class);
        }
    }
}
