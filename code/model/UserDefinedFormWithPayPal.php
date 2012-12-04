<?php

/**
 */

class UserDefinedFormWithPayPal extends UserDefinedForm {

	/**
	 * map Template Fields ($key) to fields used in the userdefined form
	 * For example, if the userdefinedforms contains a fields 'LastName'
	 * then Paypal will be presented with its value as Surname.
	 * @var Array
	 */
	public static $mapped_fields = array(
		'Surname' => array("Name", "Surname", "LastName"),
		'FirstName' => array("FirstName", "Firstname", "Name"),
		'Address1' => array("Address", "Street"),
		'Address2' => array("Address", "Address2", "Suburb"),
		'City' => array("City", "Town"),
		'State' => array("State", "Province"),
		'Zip' => array("Zip", "PostalCode", "Zipcode", "Postcode"),
		'Country' => array("Country"),
		'Email' => array("Email", "E-Mail"),
	);
		static function set_mapped_fields($a) {self::$mapped_fields = $a;}
		static function get_mapped_fields() {return self::$mapped_fields;}
		static function add_mapped_fields($key, $arrayValue) {self::$mapped_fields[$key] = $arrayValue;}
		static function remove_mapped_fields($key) {unset(self::$mapped_fields[$key]);}


	/**
	 * @var Array Fields on the user defined form page.
	 */
	static $db = array(
		"Amount" => "Double",
		"ProductName" => "Varchar(100)",
		"ProductCode" => "Varchar(10)",
		"CurrencyCode" => "Varchar(5)",
		"BusinessEmail" => "Varchar(100)",
		"PaypalButtonLabel" => "Varchar(100)",
		"BeforePaymentInstructions" => "HTMLText"
	);

	/**
	 * @var Array Default values of variables when this page is created
	 */
	static $defaults = array(
		'PaypalButtonLabel' => 'pay now'
	);

	/**
	 * Setup the CMS Fields for the User Defined Form
	 *
	 * @return FieldSet
	 */
	public function getCMSFields() {
		$fields = parent::getCMSFields();

		// define tabs
		$fields->findOrMakeTab('Root.Content.Paypal', _t('UserDefinedFormWithPayPal.PAYPAL', 'PayPal'));
		// field editor
		$fields->addFieldToTab("Root.Content.Paypal", new HeaderField("UserDefinedFormWithPayPalRequiredFields", _t('UserDefinedFormWithPayPal.REQUIREDFIELDS', 'Required Fields')));
		$fields->addFieldToTab("Root.Content.Paypal", new EmailField("BusinessEmail", _t('UserDefinedFormWithPayPal.BUSINESSEMAIL', 'Email associated with your paypal account - REQUIRED')));
		$fields->addFieldToTab("Root.Content.Paypal", new NumericField("Amount", _t('UserDefinedFormWithPayPal.AMOUNT', 'Amount / Charge')));
		$fields->addFieldToTab("Root.Content.Paypal", new TextField("ProductCode", _t('UserDefinedFormWithPayPal.PRODUCTCODE', 'Product Code, something that uniquely identifies this form / product')));
		$fields->addFieldToTab("Root.Content.Paypal", new TextField("ProductName", _t('UserDefinedFormWithPayPal.PRODUCTNAME', 'Product Name (as shown on PayPal Payment Form)')));
		$fields->addFieldToTab("Root.Content.Paypal", new HeaderField("UserDefinedFormWithPayPalNOTRequiredFields", _t('UserDefinedFormWithPayPal.OPTIONALFIELDS', 'Optional Fields')));
		$fields->addFieldToTab("Root.Content.Paypal", new TextField("CurrencyCode", _t('UserDefinedFormWithPayPal.CURRENCYCODE', 'Currency Code (e.g. NZD or USD or EUR)')));
		$fields->addFieldToTab("Root.Content.Paypal", new TextField("PaypalButtonLabel", _t('UserDefinedFormWithPayPal.PAYPALBUTTONLABEL', 'PayPal Button Text (e.g. pay now)')));
		$fields->addFieldToTab("Root.Content.Paypal", new HTMLEditorField("BeforePaymentInstructions", _t('UserDefinedFormWithPayPal.BEFOREPAYMENTINSTRUCTIONS', 'Instructions to go with payment now button (e.g. click on the button above to proceed with your payment)')));
		return $fields;
	}

	function onBeforeWrite(){
		parent::onBeforeWrite();
		if(!$this->ProductCode) {
			$this->ProductCode = $this->Link();
		}
	}


}

/**
 * Controller for the {@link UserDefinedFormWithPayPal} page type.
 *
 * @package userform
 * @subpackage pagetypes
 */

class UserDefinedFormWithPayPal_Controller extends UserDefinedForm_Controller {


	/**
	 *
	 * @var SubmittedForm Object
	 */
	protected $mostLikeLySubmission = null;

	/**
	 * Process the form that is submitted through the site
	 *
	 * @param Array Data
	 * @param Form Form
	 * @return Redirection
	 */
	function process($data, $form) {
		//pre process?
		parent::process($data, $form);
		//post process
		$this->mostLikeLySubmission = DataObject::get("SubmittedForm", null, "\"Created\" DESC", null, 1)->First();
		Session::set("UserDefinedFormWithPayPalID", $this->mostLikeLySubmission->ID);
		$paypalIdentifier1 = new SubmittedFormField();
		$paypalIdentifier1->Name = "Paypal Identifier";
		$paypalIdentifier1->Title = "Paypal Identifier";
		$paypalIdentifier1->Value = $this->getCustomCode();
		$paypalIdentifier1->ParentID = $this->mostLikeLySubmission->ID;
		$paypalIdentifier1->write();
		//add a double-check
		$paypalIdentifier2 = new SubmittedFormField();
		$paypalIdentifier2->Name = "Paypal DoubleCheck";
		$paypalIdentifier2->Title = "Paypal DoubleCheck";
		$paypalIdentifier2->Value = print_r($data, 1);
		$paypalIdentifier2->ParentID = $this->mostLikeLySubmission->ID;
		$paypalIdentifier2->write();
	}

	/**
	 * Handle notification from PAYPAL
	 * to be completed.
	 */
	function getnotification(){
		return array();
	}

	/**
	 * This action handles rendering the "finished" message,
	 * which is customisable by editing the ReceivedFormSubmission.ss
	 * template.
	 *
	 * @return ViewableData
	 */
	function finished() {
		$referrer = isset($_GET['referrer']) ? urldecode($_GET['referrer']) : null;
		$this->mostLikeLySubmission = DataObject::get_by_id("SubmittedForm", intval(Session::get("UserDefinedFormWithPayPalID"))-0);
		if($this->mostLikeLySubmission && $this->BusinessEmail) {
			$customisedArray = array(
				'Link' => $referrer,
				'BeforePaymentInstructions' => $this->BeforePaymentInstructions,
				'MerchantID' => $this->BusinessEmail,
				'ProductName' => $this->ProductName,
				'Custom' => $this->getCustomCode(),
				'SubmittedFormID' => $this->mostLikeLySubmission->ID,
				'Amount' => $this->Amount,
				'PaypalButtonLabel' => $this->PaypalButtonLabel,
				'CurrencyCode' => $this->CurrencyCode,
				'ReturnLink' => $this->Link("paymentmade")
			);
			foreach(UserDefinedFormWithPayPal::get_mapped_fields() as $templateField => $forFieldsArray){
				$customisedArray[$templateField] = $this->getSubmittedFormValue($forFieldsArray);
			}
			return $this->customise(
				array(
					'Content' => $this->customise($customisedArray)->renderWith('ReceivedFormSubmissionWithPayPal'),
					'Form' => '',
				)
			);
		}
		else {
			return parent::finished();
		}
	}

	/**
	 * This is the new finished method;
	 * @return ViewableData
	 */
	function paymentmade(){
		return parent::finished();
	}

	/**
	 * Checks for a list of fields in the submitted values to see
	 * if it is part of the submitted form
	 * e.g. if the user enters an Email in the form then we can pass this on to Paypal.
	 * @param Array - array of fields to check for
	 */
	protected function getSubmittedFormValue($nameArray) {
		if($this->mostLikeLySubmission) {
			foreach($nameArray as $name) {
				$name = Convert::raw2sql($name);
				$field = DataObject::get_one(
					"SubmittedFormField",
					" (\"Name\" = '$name' OR \"Title\" = '$name') AND \"ParentID\" = ".$this->mostLikeLySubmission->ID."
				");
				if($field) {
					return Convert::raw2att($field->Value);
				}
			}
		}
		return "";
	}

	/**
	 * returns the unique identifier for the transaction
	 * we include the page ID and Version in case we need it.
	 * @return String
	 */
	protected function getCustomCode(){
		return $this->ProductCode."-".$this->mostLikeLySubmission->ID."#".$this->ID."-".$this->Version;
	}


}
