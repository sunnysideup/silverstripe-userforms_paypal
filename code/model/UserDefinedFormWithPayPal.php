<?php

/**
 */

class UserDefinedFormWithPayPal extends UserDefinedForm
{

    /**
     * map Template Fields ($key) to fields used in the userdefined form
     * For example, if the userdefinedforms contains a fields 'LastName'
     * then Paypal will be presented with its value as Surname.
     * @var Array
     */
    private static $mapped_fields = array(
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



    /**
     * @var Array Fields on the user defined form page.
     */
    private static $db = array(
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
    private static $defaults = array(
        'PaypalButtonLabel' => 'pay now'
    );

    /**
     * Setup the CMS Fields for the User Defined Form
     *
     * @return FieldSet
     */
    public function getCMSFields()
    {
        $fields = parent::getCMSFields();

        // define tabs
        $fields->findOrMakeTab('Root.Paypal', _t('UserDefinedFormWithPayPal.PAYPAL', 'PayPal'));
        // field editor
        $fields->addFieldToTab("Root.Paypal", new HeaderField("UserDefinedFormWithPayPalRequiredFields", _t('UserDefinedFormWithPayPal.REQUIREDFIELDS', 'Required Fields')));
        $fields->addFieldToTab("Root.Paypal", new EmailField("BusinessEmail", _t('UserDefinedFormWithPayPal.BUSINESSEMAIL', 'Email associated with your paypal account - REQUIRED')));
        $fields->addFieldToTab("Root.Paypal", new NumericField("Amount", _t('UserDefinedFormWithPayPal.AMOUNT', 'Amount / Charge')));
        $fields->addFieldToTab("Root.Paypal", new TextField("ProductCode", _t('UserDefinedFormWithPayPal.PRODUCTCODE', 'Product Code, something that uniquely identifies this form / product')));
        $fields->addFieldToTab("Root.Paypal", new TextField("ProductName", _t('UserDefinedFormWithPayPal.PRODUCTNAME', 'Product Name (as shown on PayPal Payment Form)')));
        $fields->addFieldToTab("Root.Paypal", new HeaderField("UserDefinedFormWithPayPalNOTRequiredFields", _t('UserDefinedFormWithPayPal.OPTIONALFIELDS', 'Optional Fields')));
        $fields->addFieldToTab("Root.Paypal", new TextField("CurrencyCode", _t('UserDefinedFormWithPayPal.CURRENCYCODE', 'Currency Code (e.g. NZD or USD or EUR)')));
        $fields->addFieldToTab("Root.Paypal", new TextField("PaypalButtonLabel", _t('UserDefinedFormWithPayPal.PAYPALBUTTONLABEL', 'PayPal Button Text (e.g. pay now)')));
        $fields->addFieldToTab("Root.Paypal", new HtmlEditorField("BeforePaymentInstructions", _t('UserDefinedFormWithPayPal.BEFOREPAYMENTINSTRUCTIONS', 'Instructions to go with payment now button (e.g. click on the button above to proceed with your payment)')));
        return $fields;
    }

    public function onBeforeWrite()
    {
        parent::onBeforeWrite();
        if (!$this->ProductCode) {
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

class UserDefinedFormWithPayPal_Controller extends UserDefinedForm_Controller
{


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
    public function process($data, $form)
    {
        //pre process?
        parent::process($data, $form);
        //post process
        $this->mostLikeLySubmission = SubmittedForm::get()
            ->sort("Created", "DESC")
            ->limit(1)
            ->first();
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
    public function getnotification()
    {
        return array();
    }

    /**
     * This action handles rendering the "finished" message,
     * which is customisable by editing the ReceivedFormSubmission.ss
     * template.
     *
     * @return ViewableData
     */
    public function finished()
    {
        $referrer = isset($_GET['referrer']) ? urldecode($_GET['referrer']) : null;
        $this->mostLikeLySubmission = SubmittedForm::get()
            ->byID(intval(Session::get("UserDefinedFormWithPayPalID"))-0);
        if ($this->mostLikeLySubmission && $this->BusinessEmail) {
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
            $mappedFields = Config::inst()->get("UserDefinedFormWithPayPal", "mapped_fields");
            foreach ($mappedFields as $templateField => $forFieldsArray) {
                $customisedArray[$templateField] = $this->getSubmittedFormValue($forFieldsArray);
            }
            return $this->customise(
                array(
                    'Content' => $this->customise($customisedArray)->renderWith('ReceivedFormSubmissionWithPayPal'),
                    'Form' => '',
                )
            );
        } else {
            return parent::finished();
        }
    }

    /**
     * This is the new finished method;
     * @return ViewableData
     */
    public function paymentmade()
    {
        return parent::finished();
    }

    /**
     * Checks for a list of fields in the submitted values to see
     * if it is part of the submitted form
     * e.g. if the user enters an Email in the form then we can pass this on to Paypal.
     * @param Array - array of fields to check for
     */
    protected function getSubmittedFormValue($nameArray)
    {
        if ($this->mostLikeLySubmission) {
            foreach ($nameArray as $name) {
                $name = Convert::raw2sql($name);
                $field = SubmittedFormField::get()
                    ->filterAny(array("Name" => $name, "Title" => $title))
                    ->filter(array("ParentID" => $this->mostLikeLySubmission->ID))
                    ->first();
                if ($field) {
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
    protected function getCustomCode()
    {
        return $this->ProductCode."-".$this->mostLikeLySubmission->ID."#".$this->ID."-".$this->Version;
    }
}
