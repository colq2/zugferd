<?php

namespace horstoeko\zugferd;

use horstoeko\zugferd\ZUgferdProfiles;
use MimeTyper\Repository\MimeDbRepository;

class ZugferdObjectHelper
{
    /**
     * Internal profile id (see ZugferdProfiles.php)
     *
     * @var integer
     */
    public $profile = -1;

    /**
     * Internal profile definition (see ZugferdProfiles.php)
     *
     * @var array
     */
    public $profiledef = [];

    /**
     * Constructor
     *
     * @param integer $profile
     */
    public function __construct(int $profile)
    {
        $this->profile = $profile;
        $this->profiledef = ZUgferdProfiles::PROFILEDEF[$profile];
    }

    /**
     * Creates an instance of IDType
     *
     * @param string $value
     * @param string $schemeId
     * @return object
     */
    public function GetIdType($value, $schemeId = ""): ?object
    {
        $idType = $this->CreateClassInstanceIf('udt\IDType', $value, $value);

        $this->TryCall($idType, "setSchemeID", $schemeId);

        return $idType;
    }

    /**
     * Creates an instance of TextType
     *
     * @param string $value
     * @param string $schemeId
     * @return object
     */
    public function GetTextType($value): ?object
    {
        return $this->CreateClassInstanceIf('udt\TextType', $value, $value);
    }

    /**
     * Creates an instance of CodeType
     *
     * @param string $value
     * @param string $schemeId
     * @return object
     */
    public function GetCodeType($value): ?object
    {
        return $this->CreateClassInstanceIf('udt\CodeType', $value, $value);
    }

    /**
     * Get formatted issue date
     *
     * @param \DateTime $datetime
     * @return object|null
     */
    public function GetFormattedDateTimeType(?\DateTime $datetime): ?object
    {
        if ($datetime == null) {
            return null;
        }

        $date2 = $this->CreateClassInstance('qdt\FormattedDateTimeType\DateTimeStringAType');
        $this->TryCall($date2, "value", $datetime->format("Y-m-d"));
        $this->TryCall($date2, "setFormat", "102");

        $date = $this->CreateClassInstance('qdt\FormattedDateTimeType');
        $this->TryCall($date, "setDateTimeString", $date2);

        return $date;
    }

    /**
     * Get formatted issue date
     *
     * @param \DateTime $datetime
     * @return object|null
     */
    public function GetDateTimeType(?\DateTime $datetime): ?object
    {
        if ($datetime == null) {
            return null;
        }

        $date2 = $this->CreateClassInstance('udt\DateTimeType\DateTimeStringAType');
        $this->TryCall($date2, "value", $datetime->format("Y-m-d"));
        $this->TryCall($date2, "setFormat", "102");

        $date = $this->CreateClassInstance('udt\DateTimeType');
        $this->TryCall($date, "setDateTimeString", $date2);

        return $date;
    }

    /**
     * Get a BinaryObjectType object
     *
     * @param [type] $value
     * @param [type] $mimetype
     * @param [type] $filename
     * @return void
     */
    public function GetBinaryObjectType($binarydata, $mimetype, $filename)
    {
        $binaryobject = $this->CreateClassInstance('udt\BinaryObjectType');

        $this->TryCall($binaryobject, "value", $binarydata);
        $this->TryCall($binaryobject, "setMimeCode", $mimetype);
        $this->TryCall($binaryobject, "setFilename", $filename);

        return $binaryobject;
    }

    /**
     * Get a reference document object
     *
     * @param string $issuerassignedid
     * @param string|null $uriid
     * @param string|null $lineid
     * @param string|null $typecode
     * @param string|array|null $name
     * @param string|null $reftypecode
     * @param \DateTime|null $issueddate
     * @param string|null $binarydatafilename
     * @return void
     */
    public function GetReferencedDocumentType($issuerassignedid, $uriid, $lineid, $typecode, $name, $reftypecode, ?\DateTime $issueddate, $binarydatafilename)
    {
        $refdoctype = $this->CreateClassInstance('ram\ReferencedDocumentType', $issuerassignedid);

        $this->TryCall($refdoctype, 'setIssuerAssignedID', $this->GetIdType($issuerassignedid));
        $this->TryCall($refdoctype, 'setURIID', $this->GetIdType($uriid));
        $this->TryCall($refdoctype, 'setLineID', $this->GetIdType($lineid));
        $this->TryCall($refdoctype, 'setTypeCode', $this->GetCodeType($typecode));
        $this->TryCall($refdoctype, 'setReferenceTypeCode', $this->GetCodeType($reftypecode));
        $this->TryCall($refdoctype, 'setFormattedIssueDateTime', $this->GetFormattedDateTimeType($issueddate));

        foreach ($this->EnsureStringArray($name) as $name) {
            $this->TryCall($refdoctype, 'addToName', $this->GetTextType($name));
        }

        if ($binarydatafilename) {
            if (file_exists($binarydatafilename) && is_readable($binarydatafilename)) {
                $mimetyper = new MimeDbRepository();
                $content = base64_encode(file_get_contents($binarydatafilename));
                $pathParts = pathinfo($binarydatafilename);
                $this->TryCall(
                    $refdoctype,
                    'setAttachmentBinaryObject',
                    $this->GetBinaryObjectType($content, $mimetyper->findType($pathParts["extension"]), $pathParts["basename"]));
            }
        }

        return $refdoctype;
    }

    /**
     * Undocumented function
     *
     * @return \horstoeko\zugferd\entities\basic\rsm\CrossIndustryInvoiceType|\horstoeko\zugferd\entities\basicwl\rsm\CrossIndustryInvoiceType|\horstoeko\zugferd\entities\en16931\rsm\CrossIndustryInvoiceType|\horstoeko\zugferd\entities\extended\rsm\CrossIndustryInvoiceType
     */
    public function GetCrossIndustryInvoice()
    {
        $result = $this->CreateClassInstance('rsm\CrossIndustryInvoice');
        $result->setExchangedDocumentContext($this->CreateClassInstance('ram\ExchangedDocumentContextType'));
        $result->setExchangedDocument($this->CreateClassInstance('ram\ExchangedDocumentType'));
        $result->setSupplyChainTradeTransaction($this->CreateClassInstance('ram\SupplyChainTradeTransactionType'));
        $result->getExchangedDocumentContext()->setGuidelineSpecifiedDocumentContextParameter($this->CreateClassInstance('ram\DocumentContextParameterType'));
        $result->getExchangedDocumentContext()->getGuidelineSpecifiedDocumentContextParameter()->setID($this->GetIdType($this->profiledef['contextparameter']));
        $result->getSupplyChainTradeTransaction()->setApplicableHeaderTradeAgreement($this->CreateClassInstance('ram\HeaderTradeAgreementType'));
        $result->getSupplyChainTradeTransaction()->setApplicableHeaderTradeDelivery($this->CreateClassInstance('ram\HeaderTradeDeliveryType'));
        $result->getSupplyChainTradeTransaction()->setApplicableHeaderTradeSettlement($this->CreateClassInstance('ram\HeaderTradeSettlementType'));

        return $result;
    }

    /**
     * Tradeparty type
     *
     * @param [type] $ID
     * @param [type] $globalID
     * @param [type] $globalIDType
     * @param [type] $name
     * @param [type] $description
     * @param [type] $lineone
     * @param [type] $linetwo
     * @param [type] $linethree
     * @param [type] $postcode
     * @param [type] $city
     * @param [type] $country
     * @param [type] $subdivision
     * @param [type] $legalorgid
     * @param [type] $legalorgtype
     * @param [type] $legalorgname
     * @param [type] $contactpersonname
     * @param [type] $contactdepartmentname
     * @param [type] $contactphoneno
     * @param [type] $contactfaxno
     * @param [type] $contactemailaddr
     * @param [type] $taxregtype
     * @param [type] $taxregid
     * @return object
     */
    public function GetTradeParty($ID, $globalID, $globalIDType, $name, $description, $lineone, $linetwo, $linethree, $postcode, $city, $country, $subdivision, $legalorgid, $legalorgtype, $legalorgname, $contactpersonname, $contactdepartmentname, $contactphoneno, $contactfaxno, $contactemailaddr, $taxregtype, $taxregid): ?object
    {
        $tradeParty = $this->CreateClassInstance('ram\TradePartyType');
        $address = $this->GetTradeAddress($lineone, $linetwo, $linethree, $postcode, $city, $country, $subdivision);
        $legalorg = $this->GetLegalOrganization($legalorgid, $legalorgtype, $legalorgname);
        $contact = $this->GetTradeContact($contactpersonname, $contactdepartmentname, $contactphoneno, $contactfaxno, $contactemailaddr);
        $taxreg = $this->GetTaxRegistrationType($taxregtype, $taxregid);

        $this->TryCall($tradeParty, "addToID", $this->GetIdType($ID));
        $this->TryCall($tradeParty, "addToGlobalID", $this->GetIdType($globalID, $globalIDType));
        $this->TryCall($tradeParty, "setName", $this->GetTextType($name));
        $this->TryCall($tradeParty, "setDescription", $this->GetTextType($description));
        $this->TryCall($tradeParty, "setPostalTradeAddress", $address);
        $this->TryCall($tradeParty, "setSpecifiedLegalOrganization", $legalorg);
        $this->TryCall($tradeParty, "setDefinedTradeContact", $contact);
        $this->TryCall($tradeParty, "addToSpecifiedTaxRegistration", $taxreg);

        return $tradeParty;
    }

    /**
     * Address type
     *
     * @param [type] $lineone
     * @param [type] $linetwo
     * @param [type] $linethree
     * @param [type] $postcode
     * @param [type] $city
     * @param [type] $country
     * @param [type] $subdivision
     * @return object
     */
    public function GetTradeAddress($lineone, $linetwo, $linethree, $postcode, $city, $country, $subdivision): ?object
    {
        $address = $this->CreateClassInstance('ram\TradeAddressType');

        $this->TryCall($address, "setLineOne", $this->GetTextType($lineone));
        $this->TryCall($address, "setLineTwo", $this->GetTextType($linetwo));
        $this->TryCall($address, "setLineThree", $this->GetTextType($linethree));
        $this->TryCall($address, "setPostcodeCode", $this->GetCodeType($postcode));
        $this->TryCall($address, "setCityName", $this->GetTextType($city));
        $this->TryCall($address, "setCountryID", $this->GetCodeType($country));
        $this->TryCall($address, "addToCountrySubDivisionName", $this->GetTextType($subdivision));

        return $address;
    }

    /**
     * Legal organization type
     *
     * @param [type] $legalorgid
     * @param [type] $legalorgtype
     * @param [type] $legalorgname
     * @return object
     */
    public function GetLegalOrganization($legalorgid, $legalorgtype, $legalorgname): ?object
    {
        $legalorg = $this->CreateClassInstance('ram\LegalOrganizationType', $legalorgname);

        $this->TryCall($legalorg, "setID", $this->GetIdType($legalorgid, $legalorgtype));
        $this->TryCall($legalorg, "setTradingBusinessName", $this->GetTextType($legalorgname));

        return $legalorg;
    }

    /**
     * Contact type
     *
     * @param [type] $contactpersonname
     * @param [type] $contactdepartmentname
     * @param [type] $contactphoneno
     * @param [type] $contactfaxno
     * @param [type] $contactemailaddr
     * @return object
     */
    public function GetTradeContact($contactpersonname, $contactdepartmentname, $contactphoneno, $contactfaxno, $contactemailaddr): ?object
    {
        $contact = $this->CreateClassInstance('ram\TradeContactType', $contactpersonname);
        $contactphone = $this->GetUniversalCommunicationType($contactphoneno, null);
        $contactfax = $this->GetUniversalCommunicationType($contactfaxno, null);
        $contactemail = $this->GetUniversalCommunicationType(null, $contactemailaddr);

        $this->TryCall($contact, "setPersonName", $this->GetTextType($contactpersonname));
        $this->TryCall($contact, "setDepartmentName", $this->GetTextType($contactdepartmentname));
        $this->TryCall($contact, "setTelephoneUniversalCommunication", $contactphone);
        $this->TryCall($contact, "setFaxUniversalCommunication", $contactfax);
        $this->TryCall($contact, "setEmailURIUniversalCommunication", $contactemail);

        return $contact;
    }

    /**
     * Communication type
     *
     * @param [type] $number
     * @param [type] $uriid
     * @param string $urischeme
     * @return object
     */
    public function GetUniversalCommunicationType($number, $uriid, $urischeme = "SMTP"): ?object
    {
        $communication = $this->CreateClassInstanceIf('ram\UniversalCommunicationType', null, $number || $uriid);

        $this->TryCall($communication, "setCompleteNumber", $this->GetTextType($number));
        $this->TryCall($communication, "setURIID", $this->GetIdType($uriid, $urischeme));

        return $communication;
    }

    /**
     * Tax registration type
     *
     * @param [type] $taxregtype
     * @param [type] $taxregid
     * @return object
     */
    public function GetTaxRegistrationType($taxregtype, $taxregid): ?object
    {
        $taxreg = $this->CreateClassInstanceIf('ram\TaxRegistrationType', null, $taxregtype && $taxregid);

        $this->TryCall($taxreg, "setID", $this->GetIdType($taxregid, $taxregtype));

        return $taxreg;
    }

    /**
     * Delivery terms type
     *
     * @param [type] $code
     * @return object
     */
    public function GetTradeDeliveryTermsType($code): ?object
    {
        $deliveryterms = $this->CreateClassInstanceIf('ram\TradeDeliveryTermsType', null, $code);

        $this->TryCall($deliveryterms, "setDeliveryTypeCode", $this->GetCodeType($code));

        return $deliveryterms;
    }

    /**
     * Procuring project type
     *
     * @param string $id
     * @param string $name
     * @return object
     */
    public function GetProcuringProjectType($id, $name): ?object
    {
        $procuringproject = $this->CreateClassInstanceIf('ram\ProcuringProjectType', null, $id && $name);

        $this->TryCall($procuringproject, "setID", $this->GetIdType($id));
        $this->TryCall($procuringproject, "setName", $this->GetTextType($name));

        return $procuringproject;
    }

    /**
     * Undocumented function
     *
     * @param \DateTime|null $date
     * @return object
     */
    public function GetSupplyChainEventType(?\DateTime $date): ?object
    {
        $supplychainevent = $this->CreateClassInstanceIf('ram\SupplyChainEventType', null, $date);

        $this->TryCall($supplychainevent, "setOccurrenceDateTime", $this->GetDateTimeType($date));

        return $supplychainevent;
    }

    /**
     * Creates an instance of a class needed by $invoiceObject
     *
     * @param string $className
     * @param mixed $constructorvalue
     * @return object|null
     */
    public function CreateClassInstance($classname, $constructorvalue = null): ?object
    {
        $className = 'horstoeko\zugferd\entities\\' . $this->profiledef["name"] . '\\' . $classname;

        if (!class_exists($className)) {
            return null;
        }

        return new $className($constructorvalue);
    }

    /**
     * Creates an instance of a class needed by $invoiceObject if $condition is true
     *
     * @param string $classname
     * @param boolean $condition
     * @return object|null
     */
    public function CreateClassInstanceIf($classname, $constructorvalue = null, $condition = true): ?object
    {
        if (!$condition) {
            return null;
        }
        return $this->CreateClassInstance($classname, $constructorvalue);
    }

    /**
     * Tries to call a method
     *
     * @param object $instance
     * @param string $method
     * @param mixed $value
     * @return ZugferdObjectHelper
     */
    public function TryCall($instance, $method, $value)
    {
        if (!$instance) {
            return $this;
        }
        if (!$method) {
            return $this;
        }
        if (!$value) {
            return $this;
        }
        if (method_exists($instance, $method)) {
            $instance->$method($value);
        }
        return $this;
    }

    public function EnsureStringArray($input): array
    {
        if (is_array($input)) {
            return $input;
        }
        return [(string) $input];
    }
}
