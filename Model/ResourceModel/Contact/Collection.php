<?php

namespace Dotdigitalgroup\Email\Model\ResourceModel\Contact;

class Collection extends
 \Magento\Framework\Model\ResourceModel\Db\Collection\AbstractCollection
{
    protected $_idFieldName = 'email_contact_id';

    /**
     * @var \Magento\Newsletter\Model\SubscriberFactory
     */
    private $subscriberFactory;

    /**
     * Initialize resource collection.
     */
    public function _construct()
    {
        $this->_init(
            'Dotdigitalgroup\Email\Model\Contact',
            'Dotdigitalgroup\Email\Model\ResourceModel\Contact'
        );
    }

    public function __construct(
        \Magento\Framework\Data\Collection\EntityFactoryInterface $entityFactory,
        \Psr\Log\LoggerInterface $logger,
        \Magento\Framework\Data\Collection\Db\FetchStrategyInterface $fetchStrategy,
        \Magento\Framework\Event\ManagerInterface $eventManager,
        \Magento\Newsletter\Model\SubscriberFactory $subscriberFactory,
        \Magento\Framework\DB\Adapter\AdapterInterface $connection = null,
        \Magento\Framework\Model\ResourceModel\Db\AbstractDb $resource = null
    ) {
        $this->subscriberFactory = $subscriberFactory;
        parent::__construct(
            $entityFactory,
            $logger,
            $fetchStrategy,
            $eventManager,
            $connection,
            $resource
        );
    }

    /**
     * Load contact by customer id.
     *
     * @param int $customerId
     *
     * @return mixed
     */
    public function loadByCustomerId($customerId)
    {
        $collection = $this->addFieldToFilter('customer_id', $customerId)
            ->setPageSize(1);

        if ($collection->getSize()) {
            return $collection->getFirstItem();
        }

        return false;
    }

    /**
     * Get all customer contacts not imported for a website.
     *
     * @param int $websiteId
     * @param int $pageSize
     *
     * @return $this
     */
    public function getContactsToImportForWebsite($websiteId, $pageSize = 100)
    {
        $collection = $this->addFieldToFilter('website_id', $websiteId)
            ->addFieldToFilter('email_imported', ['null' => true])
            ->addFieldToFilter('customer_id', ['neq' => '0']);

        $collection->getSelect()->limit($pageSize);

        return $collection;
    }

    /**
     * Get missing contacts.
     *
     * @param int $websiteId
     * @param int $pageSize
     *
     * @return $this
     */
    public function getMissingContacts($websiteId, $pageSize = 100)
    {
        $collection = $this->addFieldToFilter('contact_id', ['null' => true])
            ->addFieldToFilter('suppressed', ['null' => true])
            ->addFieldToFilter('website_id', $websiteId);

        $collection->getSelect()->limit($pageSize);

        return $collection->load();
    }

    /**
     * Load Contact by Email.
     *
     * @param string $email
     * @param int $websiteId
     *
     * @return mixed
     */
    public function loadByCustomerEmail($email, $websiteId)
    {
        $collection = $this->addFieldToFilter('email', $email)
            ->addFieldToFilter('website_id', $websiteId)
            ->setPageSize(1);

        if ($collection->getSize()) {
            return $collection->getFirstItem();
        }

        return false;
    }

    /**
     * Contact subscribers to import for website.
     *
     * @param \Magento\Store\Model\Website $website
     * @param int $limit
     * @param bool $isCustomerCheck
     * @return $this
     */
    public function getSubscribersToImport(
        \Magento\Store\Model\Website $website,
        $limit = 1000,
        $isCustomerCheck = true
    ) {
        $storeIds = $website->getStoreIds();
        $collection = $this->addFieldToFilter('is_subscriber', ['notnull' => true])
            ->addFieldToFilter('subscriber_status', '1')
            ->addFieldToFilter('subscriber_imported', ['null' => true])
            ->addFieldToFilter('store_id', ['in' => $storeIds]);

        if ($isCustomerCheck) {
            $collection->addFieldToFilter('customer_id', ['neq' => 0]);
        } else {
            $collection->addFieldToFilter('customer_id', ['eq' => 0]);
        }

        $collection->getSelect()->limit($limit);

        return $collection;
    }

    /**
     * Contact subscribers to import for website.
     *
     * @param $emails
     *
     * @return $this
     */
    public function getSubscribersToImportFromEmails($emails)
    {
        return $this->addFieldToFilter('email', ['in' => $emails]);
    }

    /**
     * Get all not imported guests for a website.
     *
     * @param \Magento\Store\Model\Website $website
     *
     * @return $this
     */
    public function getGuests(\Magento\Store\Model\Website $website)
    {
        $guestCollection = $this->addFieldToFilter('is_guest', ['notnull' => true])
            ->addFieldToFilter('email_imported', ['null' => true])
            ->addFieldToFilter('website_id', $website->getId());

        return $guestCollection->load();
    }

    /**
     * Number contacts marked as imported.
     *
     * @return int
     */
    public function getNumberOfImportedContacts()
    {
        $this->addFieldToFilter('email_imported', ['notnull' => true]);

        return $this->getSize();
    }

    /**
     * Get the number of customers for a website.
     *
     * @param int $websiteId
     *
     * @return int
     */
    public function getNumberCustomerContacts($websiteId = 0)
    {
        return $this->addFieldToFilter('customer_id', ['gt' => '0'])
            ->addFieldToFilter('website_id', $websiteId)
            ->getSize();
    }

    /**
     * Get number of suppressed contacts as customer.
     *
     * @param int $websiteId
     *
     * @return int
     */
    public function getNumberCustomerSuppressed($websiteId = 0)
    {
        return $this->addFieldToFilter('customer_id', ['gt' => 0])
            ->addFieldToFilter('website_id', $websiteId)
            ->addFieldToFilter('suppressed', '1')
            ->getSize();
    }

    /**
     * Get number of synced customers.
     *
     * @param int $websiteId
     *
     * @return int
     */
    public function getNumberCustomerSynced($websiteId = 0)
    {
        return $this->addFieldToFilter('customer_id', ['gt' => 0])
            ->addFieldToFilter('website_id', $websiteId)
            ->addFieldToFilter('email_imported', '1')
            ->getSize();
    }

    /**
     * Get number of subscribers synced.
     *
     * @param int $websiteId
     *
     * @return int
     */
    public function getNumberSubscribersSynced($websiteId = 0)
    {
        return $this->addFieldToFilter(
                'subscriber_status',
                \Dotdigitalgroup\Email\Model\Newsletter\Subscriber::STATUS_SUBSCRIBED
            )
            ->addFieldToFilter('subscriber_imported', '1')
            ->addFieldToFilter('website_id', $websiteId)
            ->getSize();
    }

    /**
     * Get number of subscribers.
     *
     * @param int $websiteId
     *
     * @return int
     */
    public function getNumberSubscribers($websiteId = 0)
    {
        return $this->addFieldToFilter(
                'subscriber_status',
                \Dotdigitalgroup\Email\Model\Newsletter\Subscriber::STATUS_SUBSCRIBED
            )
            ->addFieldToFilter('website_id', $websiteId)
            ->getSize();
    }

    /**
     * Get subscribers data by emails
     *
     * @param $emails
     * @return array
     */
    public function getSubscriberDataByEmails($emails)
    {
        $subscriberFactory = $this->subscriberFactory->create();
        $subscribersData = $subscriberFactory->getCollection()
            ->addFieldToFilter(
                'subscriber_email',
                ['in' => $emails]
            )
            ->addFieldToSelect(['subscriber_email', 'store_id']);

        return $subscribersData->toArray();
    }

    /**
     * Get contacts ti import by website
     *
     * @param $websiteId
     * @param $syncLimit
     * @return $this
     */
    public function getContactsToImportByWebsite($websiteId, $syncLimit)
    {
        return $this->addFieldToSelect('*')
            ->addFieldToFilter('customer_id', ['neq' => '0'])
            ->addFieldToFilter('website_id', $websiteId)
            ->setPageSize($syncLimit);
    }
}
