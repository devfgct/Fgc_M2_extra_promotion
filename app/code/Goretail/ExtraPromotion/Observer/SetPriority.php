<?php
namespace Goretail\ExtraPromotion\Observer;
use Magento\Framework\Event\ObserverInterface;

class SetPriority implements ObserverInterface {
	protected $_coreRegistry;
	protected $_authSession;

	public function __construct(
		\Magento\Framework\Registry $coreRegistry,
		\Magento\Backend\Model\Auth\Session $authSession,
		\Magento\Customer\Model\Session $customerSession,
		\Magento\Framework\App\RequestInterface $request
	) {
		$this->_coreRegistry = $coreRegistry;
		$this->_authSession = $authSession;
		$this->_customerSession = $customerSession;
		$this->_request = $request;
		$this->_objectManager = \Magento\Framework\App\ObjectManager::getInstance();
	}
    public function execute(\Magento\Framework\Event\Observer $observer) {
		$event = $observer->getEvent();
		$eventName = $event->getName();
		//$design = $objectManager->create('\Magento\Framework\View\DesignInterface');
		//$model = $this->_objectManager->create(\Magento\SalesRule\Model\Rule::class);
		$serializer = $this->_objectManager->get(\Magento\Framework\Serialize\Serializer\Json::class);

		$request = $event->getRequest();
		$sortOrder = $request->getPostValue('sort_order');
		$conditionsSerialized = $request->getPostValue('conditions_serialized');
		$conditionsUnserialize = $serializer->unserialize($conditionsSerialized);
		if(isset($conditionsUnserialize['conditions'])) {
			foreach ($conditionsUnserialize['conditions'] as $condition) {
				if($sortOrder < 1 && $condition['attribute'] == 'base_grand_total') {
					$request->setPostValue('sort_order', 100);
				}
			}
		}
		return $this;
	}
}
