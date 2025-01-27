.. include:: ../../Includes.txt

.. _extending:

======================
Changing and Extending
======================

If you need additional functionality or the existing functionality of the extension isn't quite what you need, this section tells you how to change the behavior of the Interest extension. It also tells you how to extend the functionality, as well as a bit about the extension's inner workings.

.. _extending-events:

PSR-14 Events
=============

.. _extending-events-list:

Events
------

The events are listed in order of execution.

.. php:namespace:: FriendsOfTYPO3\Interest\Router\Event

.. php:class:: HttpRequestRouterHandleByEvent

   Called in :php:`HttpRequestRouter::handleByMethod()`. Can be used to modify the request and entry point parts before they are passed on to a RequestHandler.

   EventHandlers for this event should implement :php:`FriendsOfTYPO3\Interest\Router\Event\HttpRequestRouterHandleByEventHandlerInterface`.

   .. php:method:: getEntryPointParts()

      Returns an array of the entry point parts, i.e. the parts of the URL used to detect the correct entry point. Given the URL `http://www.example.com/rest/tt_content/ContentRemoteId` and the default entry point `rest`, the entry point parts will be :php:`['tt_content', 'ContentRemoteId']`.

      :returntype: array

   .. php:method:: setEntryPointParts($entryPointParts)

      :param array $entryPointParts:

   .. php:method:: getRequest()

      :returntype: Psr\Http\Message\ServerRequestInterface

   .. php:method:: setRequest($request)

      :param Psr\Http\Message\ServerRequestInterface $request:

.. php:namespace::  FriendsOfTYPO3\Interest\DataHandling\Operation\Event

.. php:class:: RecordOperationSetupEvent

   Called inside the :php:`AbstractRecordOperation::__construct()` when a :php:`*RecordOperation` object has been initialized, but before data validations.

   EventHandlers for this event should implement :php:`FriendsOfTYPO3\Interest\DataHandling\Operation\Event\RecordOperationEventHandlerInterface`.

   EventHandlers for this event can throw these exceptions:

   :php:`FriendsOfTYPO3\Interest\DataHandling\Operation\Event\Exception\StopRecordOperationException`
      To quietly stop the record operation. This exception is only logged as informational and the operation will be treated as successful. E.g. used when deferring an operation.

   :php:`FriendsOfTYPO3\Interest\DataHandling\Operation\Event\Exception\BeforeRecordOperationEventException`
      Will stop the record operation and log as an error. The operation will be treated as unsuccessful.

   .. php:method:: getRecordOperation()

      :returntype: FriendsOfTYPO3\Interest\DataHandling\Operation\AbstractRecordOperation

.. php:namespace::  FriendsOfTYPO3\Interest\DataHandling\Operation\Event

.. php:class:: RecordOperationInvocationEvent

   Called as the last thing inside the :php:`AbstractRecordOperation::__invoke()` method, after all data persistence and pending relations have been resolved.

   EventHandlers for this event should implement :php:`FriendsOfTYPO3\Interest\DataHandling\Operation\Event\AfterRecordOperationEventHandlerInterface`.

   .. php:method:: getRecordOperation()

      :returntype: FriendsOfTYPO3\Interest\DataHandling\Operation\AbstractRecordOperation

.. php:namespace::  FriendsOfTYPO3\Interest\Middleware\Event

.. php:class:: HttpResponseEvent

   Called in the middleware, just before control is handled back over to TYPO3 during an HTTP request. Allows modification of the response object.

   EventHandlers for this event should implement :php:`FriendsOfTYPO3\Interest\Middleware\Event\HttpResponseEventHandlerInterface`.

   .. php:method:: getResponse()

      :returntype: Psr\Http\Message\ResponseInterface

   .. php:method:: setResponse($response)

      :param Psr\Http\Message\ResponseInterface $response:

.. _extending-how-it-works:

How it works
============

.. _extending-record-representation:

Internal representation and identity
------------------------------------

Inside the extension, a record's state and identity is maintained by two data transfer object classes:

* **A record's unique identity** from creation to deletion is represented by :php:`FriendsOfTYPO3\Interest\Domain\Model\Dto\RecordInstanceIdentifier`.
* **A record's current state**, including the data that should be written to the database is represented by :php:`FriendsOfTYPO3\Interest\Domain\Model\Dto\RecordRepresentation`.

When creating a :php:`RecordRepresentation`, you must also supply a :php:`RecordInstanceIdentifier`:

.. code-block::

   use FriendsOfTYPO3\Interest\Domain\Model\Dto\RecordInstanceIdentifier;
   use FriendsOfTYPO3\Interest\Domain\Model\Dto\RecordRepresentation;

   new RecordRepresentation(
       [
           'title' => 'My record title',
           'bodytext' => 'This is a story about ...',
       ],
       new RecordInstanceIdentifier(
           'tt_content',
           'ContentElementA',
           'en'
       )
   );

.. _extending-record-operations:

Record operations
-----------------

Record operations are the core of the Interest extension. Each represents one operation requested from the outside. One record operation is not the same as one database operation. Some record operations will not be executed (if it is a duplicate of the previous operation on the same remote ID) or deferred (if the record operation requires a condition to be fulfilled before it can be executed).

The record operations are invokable, and are executed as such:

.. code-block: php

   (CreateRecordOperation(/* ... */))();

.. _extending-record-operation-types:

Record operation types
~~~~~~~~~~~~~~~~~~~~~~

There are three record operations:

* Create
* Update
* Delete

All are subclasses of :php:`FriendsOfTYPO3\Interest\DataHandling\Operation\AbstractRecordOperation`, and share its API. :php:`CreateRecordOperation` and :php:`CreateRecordOperation` are direct subclasses of :php:`AbstractConstructiveRecordOperation`, which adds a more complex constructor.

.. php:namespace:: FriendsOfTYPO3\Interest\DataHandling\Operation

.. php:class:: AbstractConstructiveRecordOperation

.. php:class:: CreateRecordOperation

.. php:class:: UpdateRecordOperation

   .. php:method:: __construct($recordRepresentation, $metaData)

      :param FriendsOfTYPO3\Interest\Domain\Model\Dto\RecordRepresentation $recordRepresentation:

      :param array $metaData:

   .. php:method:: getContentRenderer()

      Returns a special :php:`ContentObjectRenderer` for this operation. The data array is populated with operation-specific information when the operation object is initialized. It is not updated if this information changes.

      .. code-block:: php

         $contentObjectRenderer->data = [
            'table' => $this->getTable(),
            'remoteId' => $this->getRemoteId(),
            'language' => $this->getLanguage()->getHreflang(),
            'workspace' => null,
            'metaData' => $this->getMetaData(),
            'data' => $this->getDataForDataHandler(),
        ];

      :returntype: TYPO3\CMS\Frontend\ContentObject\ContentObjectRenderer

   .. php:method:: dispatchMessage($message)

      :param \FriendsOfTYPO3\Interest\DataHandling\Operation\Message\MessageInterface $message:

      Dispatch a message, to be picked up later, in another part of the operation's execution flow.

      :returntype: mixed

   .. php:method:: getDataFieldForDataHandler($fieldName)

      :param string $fieldName:

      Get the value of a specific field in the data for DataHandler. Same as :php:`$this->getDataForDataHandler()[$fieldName]`.

      :returntype: mixed

   .. php:method:: getDataForDataHandler()

      Get the data that will be written to the DataHandler. This is a modified version of the data in :php:`$this->getRecordRepresentation()->getData()`.

      :returntype: array

   .. php:method:: getDataHandler()

      Returns the internal DataHandler object used in the operation.

      :returntype: \FriendsOfTYPO3\Interest\DataHandling\DataHandler

   .. php:method:: getHash()

      Get the unique hash of this operation. The hash is generated when the operation object is initialized, and it is not changed. This hash makes it possible for the Interest extension to know whether the same operation has been run before.

      :returntype: string

   .. php:method:: getLanguage()

      Returns the record language represented by a :php:`\TYPO3\CMS\Core\Site\Entity\SiteLanguage` object, if set. :php:`$this->getRecordRepresentation()->getRecordInstanceIdentifier()->getLanguage()`

      :returntype: \TYPO3\CMS\Core\Site\Entity\SiteLanguage|null

   .. php:method:: getMetaData()

      Returns the metadata array for the operation. This metadata is not used other than to generate the uniqueness hash for the operation. You can use it to transfer useful information, e.g. for transformations. See: :ref:`userts-accessing-metadata`

      :returntype: array

   .. php:method:: getRemoteId()

      Returns the table name. Shortcut for :php:`$this->getRecordRepresentation()->getRecordInstanceIdentifier()->getRemoteIdWithAspects()`

      :returntype: string

   .. php:method:: getTable()

      Returns the table name. Shortcut for :php:`$this->getRecordRepresentation()->getRecordInstanceIdentifier()->getTable()`

      :returntype: string

   .. php:method:: getRecordRepresentation()

      :returntype: FriendsOfTYPO3\Interest\Domain\Model\Dto\RecordRepresentation

   .. php:method:: getStoragePid()

      Gets the PID of the record as originally set during object construction, usually by the :php:`\FriendsOfTYPO3\Interest\DataHandling\Operation\Event\Handler\ResolveStoragePid` event.

      :returntype: void

   .. php:method:: getSettings()

      Returns the settings array from UserTS (`tx_interest.*`).

      :returntype: array

   .. php:method:: getUid()

      Returns the record UID, or zero if not yet set. :php:`$this->getRecordRepresentation()->getRecordInstanceIdentifier()->getUid()`

      :returntype: int

   .. php:method:: getUidPlaceholder()

      Returns a DataHandler UID placeholder. If it has not yet been set, it will be generated as a random string prefixed with "NEW". :php:`$this->getRecordRepresentation()->getRecordInstanceIdentifier()->getUidPlaceholder()`

      :returntype: string

   .. php:method:: hasExecuted()

      Returns true if the operation has executed the DataHandler operations.

      :returntype: bool

   .. php:method:: isDataFieldSet($fieldName)

      :param string $fieldName:

      Check if a field in the data array is set. Same as :php:`isset($this->getDataForDataHandler()[$fieldName])`.

      :returntype: bool

   .. php:method:: isSuccessful()

      Returns true if the operation has executed the DataHandler operations without errors.

      :returntype: bool

   .. php:method:: retrieveMessage($message)

      :param string $messageFqcn:

      Pick the last message of class :php:`$messageFqcn` from the message queue. Returns null if no messages are left in the queue.

      :returntype: \FriendsOfTYPO3\Interest\DataHandling\Operation\Message\MessageInterface|null

   .. php:method:: setDataFieldForDataHandler($fieldName, $value)

      :param string $fieldName:

      Set the value of a specific field in the data for DataHandler. Same as:

      .. code-block: php

         $data = $this->getDataForDataHandler();

         $data[$fieldName] = $value;

         $this->setDataForDataHandler($data);

      :returntype: void

   .. php:method:: setDataForDataHandler($dataForDataHandler)

      Set the data that will be written to the DataHandler.

      :param array $dataForDataHandler:

   .. php:method:: setHash($hash)

      :param string $hash:

      Override the record operation's uniqueness hash. Changing this value can have severe consequences for data integrity.

      :returntype: void

   .. php:method:: setStoragePid($storagePid)

      :param int $storagePid:

      Sets the storage PID. This might override a PID set by the :php:`\FriendsOfTYPO3\Interest\DataHandling\Operation\Event\Handler\ResolveStoragePid` event, which usually handles this task.

      :returntype: void

   .. php:method:: setUid($uid)

      :param int $uid:

      Sets the record UID. :php:`$this->getRecordRepresentation()->getRecordInstanceIdentifier()->setUid($uid)`

      :returntype: void

   .. php:method:: unsetDataField($fieldName)

      :param string $fieldName:

      Unset a field in the data array. Same as:

      .. code-block: php

         $data = $this->getDataForDataHandler();

         unset($data[$fieldName]);

         $this->setDataForDataHandler($data);

      :returntype: void

.. php:class:: DeleteRecordOperation

   .. php:method:: __construct($recordRepresentation)

      You cannot send metadata information to a delete operation.

      :param FriendsOfTYPO3\Interest\Domain\Model\Dto\RecordRepresentation $recordRepresentation:

.. _extending-record-operation-messages:

Record Operation Messages
~~~~~~~~~~~~~~~~~~~~~~~~~

Classes implementing :php:`\FriendsOfTYPO3\Interest\DataHandling\Operation\Message\MessageInterface` can be used to carry information within the execution flow of an instance of :php:`\FriendsOfTYPO3\Interest\DataHandling\Operation\AbstractRecordOperation`. This is especially useful between EventHandlers.

For example, :php:`\FriendsOfTYPO3\Interest\DataHandling\Operation\Event\Handler\Message\PendingRelationMessage` is used to carry information about pending relations between the event that discovers them and the event that persists the information to the database — if the record operation was successful.

Sending a message in :php:`\FriendsOfTYPO3\Interest\DataHandling\Operation\Event\Handler\MapUidsAndExtractPendingRelations`:

.. code-block: php

   if ($pendingRelations !== []) {
       $this->recordOperation->dispatchMessage(
           new PendingRelationMessage(
               $this->recordOperation->getTable(),
               $fieldName,
               $pendingRelations
           )
       );
   }

Retrieving messages and using the message data to persist the information to the database in :php:`\FriendsOfTYPO3\Interest\DataHandling\Operation\Event\Handler\PersistPendingRelationInformation`:

.. code-block: php

   do {
       /** @var PendingRelationMessage $message */
       $message = $event->getRecordOperation()->retrieveMessage(PendingRelationMessage::class);

       if ($message !== null) {
           $repository->set(
               $message->getTable(),
               $message->getField(),
               $event->getRecordOperation()->getUid(),
               $message->getRemoteIds()
           );
       }
   } while ($message !== null);

.. _extending-mapping:

Mapping table
-------------

The extension keeps track of the mapping between remote IDs and TYPO3 records in the table `tx_interest_remote_id_mapping`. In addition to mapping information, the table contains metadata about each record.

.. warning::

   You should never access the `tx_interest_remote_id_mapping` table directly, but use the classes and methods described here.

.. _extending-touch:

Touching and the touched
~~~~~~~~~~~~~~~~~~~~~~~~

When a record is created or updated, the `touched` timestamp is updated. The timestamp is also updated if the remote request *intended* to update the record, but the Interest extension decided not to do it, for example because there was nothing to change. In this way, the time a record was last touched may more recent than the record's modification date.

The time the record was last touched can help you verify that a request was processed — or to find the remote IDs that were not mentioned at all. In the latter case, knowing remote IDs that are no longer updated regularly can tell you which remote IDs should be deleted.

.. _extending-touch-methods:

Relevant methods
^^^^^^^^^^^^^^^^

.. php:namespace:: FriendsOfTYPO3\Interest\Domain\Repository

.. php:class:: RemoteIdMappingRepository

   .. php:method:: touch($remoteId)

      Touches the remote ID and nothing else. Sets the `touched` timestamp for the remote ID to the current time.

      :param string $remoteId: The remote ID of the record to touch.

   .. php:method:: touched($remoteId)

      Returns the touched timestamp for the record.

      :param string $remoteId: The remote ID of the record to touch.

      :returntype: int

   .. php:method:: findAllUntouchedSince($timestamp, $excludeManual = true)

      Returns an array containing all remote IDs that have *not* been touched since :php:`$timestamp`.

      :param int $timestamp: Unix timestamp.

      :param bool $excludeManual: When true, remote IDs flagged as manual will be excluded from the result. Usually a good idea, as manual entries aren't usually a part of any update workflow.

      :returntype: bool

   .. php:method:: findAllTouchedSince($timestamp, $excludeManual = true)

      Returns an array containing all remote IDs that have been touched since :php:`$timestamp`.

      :param int $timestamp: Unix timestamp.

      :param bool $excludeManual: When true, remote IDs flagged as manual will be excluded from the result. Usually a good idea, as manual entries aren't usually a part of any update workflow.

      :returntype: bool

.. _extending-touch-example:

Example
^^^^^^^

Fetching all remote IDs that have not been touched since the same time yesterday.

.. code-block:: php

   use FriendsOfTYPO3\Interest\Domain\Model\Dto\RecordInstanceIdentifier;
   use FriendsOfTYPO3\Interest\Domain\Model\Dto\RecordRepresentation;
   use FriendsOfTYPO3\Interest\Domain\Repository\RemoteIdMappingRepository;
   use FriendsOfTYPO3\Interest\DataHandling\Operation\DeleteRecordOperation;
   use TYPO3\CMS\Core\Utility\GeneralUtility;

   $mappingRepository = GeneralUtility::makeInstance(RemoteIdMappingRepository::class);

   foreach($mappingRepository->findAllUntouchedSince(time() - 86400) as $remoteId) {
       (new DeleteRecordOperation(
           new RecordRepresentation(
               [],
               new RecordInstanceIdentifier('table', $remoteId)
           );
       ))();
   }

.. _extending-mapping-metadata:

Metadata
~~~~~~~~

The mapping table also contains a field that can contain serialized meta information about the record. Any class can add and retrieve meta information from this field.

Here's two existing use cases:

* **Foreign relation sorting order** by :php:`\FriendsOfTYPO3\Interest\DataHandling\Operation\Event\Handler\ForeignRelationSortingEventHandler`
* **File modification info** by :php:`\FriendsOfTYPO3\Interest\DataHandling\Operation\Event\Handler\PersistFileDataEventHandler`

.. warning::

   Make sure that you don't mix up the metadata in the mapping table with the metadata that is sent to operations, e.g. using the `metaData` property or the `--metaData` option. These are not related.

.. note::

   The field data is encoded as JSON. Any objects must be serialized so they can be stored as a string.

.. _extending-mapping-metadata-methods:

Relevant methods
^^^^^^^^^^^^^^^^

.. php:namespace:: FriendsOfTYPO3\Interest\Domain\Repository

.. php:class:: RemoteIdMappingRepository

   .. php:method:: getMetaData($remoteId)

      Retrieves all of the metadata entries as a key-value array.

      :param string $remoteId: The remote ID of the record to return the metadata for.

      :returntype: array

   .. php:method:: getMetaDataValue($remoteId, $key)

      Retrieves a metadata entry.

      :param string $remoteId: The remote ID of the record to return the metadata for.

      :param string $key: The originator class's fully qualified class name.

      :returntype: string, float, int, array, or null

   .. php:method:: getMetaDataValue($remoteId, $key, $value)

      Sets a metadata entry.

      :param string $remoteId: The remote ID of the record to return the metadata for.

      :param string $key: The originator class's fully qualified class name.

      :param string|float|int|array|null $value: The value to set.

.. _extending-mapping-metadata-example:

Example
^^^^^^^

This simplified excerpt from :php:`PersistFileDataEventHandler` shows how metadata stored in the record is used to avoid downloading a file if it hasn't changed. If it has changed, new metadata is set.

.. code-block:: php

   use GuzzleHttp\Client;
   use FriendsOfTYPO3\Interest\Domain\Repository\RemoteIdMappingRepository;

   $mappingRepository = GeneralUtility::makeInstance(RemoteIdMappingRepository::class);

   $metaData = $mappingRepository->getMetaDataValue(
       $remoteId,
       self::class
   ) ?? [];

   $headers = [
       'If-Modified-Since' => $metaData['date'],
       'If-None-Match'] => $metaData['etag'],
   ];

   $response = GeneralUtility::makeInstance(Client::class)
       ->get($url, ['headers' => $headers]);

   if ($response->getStatusCode() === 304) {
       return null;
   }

   $mappingRepository->setMetaDataValue(
       $remoteId,
       self::class,
       [
           'date' => $response->getHeader('Date'),
           'etag' => $response->getHeader('ETag'),
       ]
   );

   return $response->getBody()->getContents();
