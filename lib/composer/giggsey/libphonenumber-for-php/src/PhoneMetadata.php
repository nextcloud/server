<?php

namespace libphonenumber;

/**
 * Class PhoneMetadata
 * @package libphonenumber
 * @internal Used internally, and can change at any time
 */
class PhoneMetadata
{
    /**
     * @var string
     */
    protected $id;
    /**
     * @var int
     */
    protected $countryCode;
    protected $leadingDigits;
    protected $internationalPrefix;
    protected $preferredInternationalPrefix;
    protected $nationalPrefixForParsing;
    protected $nationalPrefixTransformRule;
    protected $nationalPrefix;
    protected $preferredExtnPrefix;
    protected $mainCountryForCode = false;
    protected $leadingZeroPossible = false;
    protected $mobileNumberPortableRegion = false;
    protected $generalDesc;
    /**
     * @var PhoneNumberDesc
     */
    protected $mobile;
    protected $premiumRate;
    protected $fixedLine;
    protected $sameMobileAndFixedLinePattern = false;
    protected $numberFormat = array();
    protected $tollFree;
    protected $sharedCost;
    protected $personalNumber;
    protected $voip;
    protected $pager;
    protected $uan;
    protected $emergency;
    protected $voicemail;
    /**
     * @var PhoneNumberDesc
     */
    protected $short_code;
    /**
     * @var PhoneNumberDesc
     */
    protected $standard_rate;
    /**
     * @var PhoneNumberDesc
     */
    protected $carrierSpecific;
    /**
     * @var PhoneNumberDesc
     */
    protected $smsServices;
    /**
     * @var PhoneNumberDesc
     */
    protected $noInternationalDialling;
    /**
     *
     * @var NumberFormat[]
     */
    protected $intlNumberFormat = array();

    /**
     * @return boolean
     */
    public function hasId()
    {
        return $this->id !== null;
    }

    /**
     * @return boolean
     */
    public function hasCountryCode()
    {
        return $this->countryCode !== null;
    }

    public function hasInternationalPrefix()
    {
        return $this->internationalPrefix !== null;
    }

    public function hasMainCountryForCode()
    {
        return $this->mainCountryForCode !== null;
    }

    public function isMainCountryForCode()
    {
        return $this->mainCountryForCode;
    }

    public function getMainCountryForCode()
    {
        return $this->mainCountryForCode;
    }

    public function setMainCountryForCode($value)
    {
        $this->mainCountryForCode = $value;
        return $this;
    }

    public function clearMainCountryForCode()
    {
        $this->mainCountryForCode = false;
        return $this;
    }

    public function hasLeadingZeroPossible()
    {
        return $this->leadingZeroPossible !== null;
    }

    public function hasMobileNumberPortableRegion()
    {
        return $this->mobileNumberPortableRegion !== null;
    }

    public function hasSameMobileAndFixedLinePattern()
    {
        return $this->sameMobileAndFixedLinePattern !== null;
    }

    public function numberFormatSize()
    {
        return \count($this->numberFormat);
    }

    /**
     * @param int $index
     * @return NumberFormat
     */
    public function getNumberFormat($index)
    {
        return $this->numberFormat[$index];
    }

    public function intlNumberFormatSize()
    {
        return \count($this->intlNumberFormat);
    }

    public function getIntlNumberFormat($index)
    {
        return $this->intlNumberFormat[$index];
    }

    public function clearIntlNumberFormat()
    {
        $this->intlNumberFormat = array();
        return $this;
    }

    public function toArray()
    {
        $output = array();

        if ($this->hasGeneralDesc()) {
            $output['generalDesc'] = $this->getGeneralDesc()->toArray();
        }

        if ($this->hasFixedLine()) {
            $output['fixedLine'] = $this->getFixedLine()->toArray();
        }

        if ($this->hasMobile()) {
            $output['mobile'] = $this->getMobile()->toArray();
        }

        if ($this->hasTollFree()) {
            $output['tollFree'] = $this->getTollFree()->toArray();
        }

        if ($this->hasPremiumRate()) {
            $output['premiumRate'] = $this->getPremiumRate()->toArray();
        }

        if ($this->hasPremiumRate()) {
            $output['premiumRate'] = $this->getPremiumRate()->toArray();
        }

        if ($this->hasSharedCost()) {
            $output['sharedCost'] = $this->getSharedCost()->toArray();
        }

        if ($this->hasPersonalNumber()) {
            $output['personalNumber'] = $this->getPersonalNumber()->toArray();
        }

        if ($this->hasVoip()) {
            $output['voip'] = $this->getVoip()->toArray();
        }

        if ($this->hasPager()) {
            $output['pager'] = $this->getPager()->toArray();
        }

        if ($this->hasUan()) {
            $output['uan'] = $this->getUan()->toArray();
        }

        if ($this->hasEmergency()) {
            $output['emergency'] = $this->getEmergency()->toArray();
        }

        if ($this->hasVoicemail()) {
            $output['voicemail'] = $this->getVoicemail()->toArray();
        }

        if ($this->hasShortCode()) {
            $output['shortCode'] = $this->getShortCode()->toArray();
        }

        if ($this->hasStandardRate()) {
            $output['standardRate'] = $this->getStandardRate()->toArray();
        }

        if ($this->hasCarrierSpecific()) {
            $output['carrierSpecific'] = $this->getCarrierSpecific()->toArray();
        }

        if ($this->hasSmsServices()) {
            $output['smsServices'] = $this->getSmsServices()->toArray();
        }

        if ($this->hasNoInternationalDialling()) {
            $output['noInternationalDialling'] = $this->getNoInternationalDialling()->toArray();
        }

        $output['id'] = $this->getId();
        if ($this->hasCountryCode()) {
            $output['countryCode'] = $this->getCountryCode();
        }

        if ($this->hasInternationalPrefix()) {
            $output['internationalPrefix'] = $this->getInternationalPrefix();
        }

        if ($this->hasPreferredInternationalPrefix()) {
            $output['preferredInternationalPrefix'] = $this->getPreferredInternationalPrefix();
        }

        if ($this->hasNationalPrefix()) {
            $output['nationalPrefix'] = $this->getNationalPrefix();
        }

        if ($this->hasPreferredExtnPrefix()) {
            $output['preferredExtnPrefix'] = $this->getPreferredExtnPrefix();
        }

        if ($this->hasNationalPrefixForParsing()) {
            $output['nationalPrefixForParsing'] = $this->getNationalPrefixForParsing();
        }

        if ($this->hasNationalPrefixTransformRule()) {
            $output['nationalPrefixTransformRule'] = $this->getNationalPrefixTransformRule();
        }

        if ($this->hasSameMobileAndFixedLinePattern()) {
            $output['sameMobileAndFixedLinePattern'] = $this->getSameMobileAndFixedLinePattern();
        }

        $output['numberFormat'] = array();
        foreach ($this->numberFormats() as $numberFormat) {
            $output['numberFormat'][] = $numberFormat->toArray();
        }

        $output['intlNumberFormat'] = array();
        foreach ($this->intlNumberFormats() as $intlNumberFormat) {
            $output['intlNumberFormat'][] = $intlNumberFormat->toArray();
        }

        $output['mainCountryForCode'] = $this->getMainCountryForCode();

        if ($this->hasLeadingDigits()) {
            $output['leadingDigits'] = $this->getLeadingDigits();
        }

        if ($this->hasLeadingZeroPossible()) {
            $output['leadingZeroPossible'] = $this->isLeadingZeroPossible();
        }

        if ($this->hasMobileNumberPortableRegion()) {
            $output['mobileNumberPortableRegion'] = $this->isMobileNumberPortableRegion();
        }

        return $output;
    }

    public function hasGeneralDesc()
    {
        return $this->generalDesc !== null;
    }

    /**
     * @return PhoneNumberDesc
     */
    public function getGeneralDesc()
    {
        return $this->generalDesc;
    }

    public function setGeneralDesc(PhoneNumberDesc $value)
    {
        $this->generalDesc = $value;
        return $this;
    }

    public function hasFixedLine()
    {
        return $this->fixedLine !== null;
    }

    /**
     * @return PhoneNumberDesc
     */
    public function getFixedLine()
    {
        return $this->fixedLine;
    }

    public function setFixedLine(PhoneNumberDesc $value)
    {
        $this->fixedLine = $value;
        return $this;
    }

    public function hasMobile()
    {
        return $this->mobile !== null;
    }

    /**
     * @return PhoneNumberDesc
     */
    public function getMobile()
    {
        return $this->mobile;
    }

    public function setMobile(PhoneNumberDesc $value)
    {
        $this->mobile = $value;
        return $this;
    }

    public function hasTollFree()
    {
        return $this->tollFree !== null;
    }

    /**
     * @return PhoneNumberDesc
     */
    public function getTollFree()
    {
        return $this->tollFree;
    }

    public function setTollFree(PhoneNumberDesc $value)
    {
        $this->tollFree = $value;
        return $this;
    }

    public function hasPremiumRate()
    {
        return $this->premiumRate !== null;
    }

    /**
     * @return PhoneNumberDesc
     */
    public function getPremiumRate()
    {
        return $this->premiumRate;
    }

    public function setPremiumRate(PhoneNumberDesc $value)
    {
        $this->premiumRate = $value;
        return $this;
    }

    public function hasSharedCost()
    {
        return $this->sharedCost !== null;
    }

    /**
     * @return PhoneNumberDesc
     */
    public function getSharedCost()
    {
        return $this->sharedCost;
    }

    public function setSharedCost(PhoneNumberDesc $value)
    {
        $this->sharedCost = $value;
        return $this;
    }

    public function hasPersonalNumber()
    {
        return $this->personalNumber !== null;
    }

    /**
     * @return PhoneNumberDesc
     */
    public function getPersonalNumber()
    {
        return $this->personalNumber;
    }

    public function setPersonalNumber(PhoneNumberDesc $value)
    {
        $this->personalNumber = $value;
        return $this;
    }

    public function hasVoip()
    {
        return $this->voip !== null;
    }

    /**
     * @return PhoneNumberDesc
     */
    public function getVoip()
    {
        return $this->voip;
    }

    public function setVoip(PhoneNumberDesc $value)
    {
        $this->voip = $value;
        return $this;
    }

    public function hasPager()
    {
        return $this->pager !== null;
    }

    /**
     * @return PhoneNumberDesc
     */
    public function getPager()
    {
        return $this->pager;
    }

    public function setPager(PhoneNumberDesc $value)
    {
        $this->pager = $value;
        return $this;
    }

    public function hasUan()
    {
        return $this->uan !== null;
    }

    /**
     * @return PhoneNumberDesc
     */
    public function getUan()
    {
        return $this->uan;
    }

    public function setUan(PhoneNumberDesc $value)
    {
        $this->uan = $value;
        return $this;
    }

    public function hasEmergency()
    {
        return $this->emergency !== null;
    }

    /**
     * @return PhoneNumberDesc
     */
    public function getEmergency()
    {
        return $this->emergency;
    }

    public function setEmergency(PhoneNumberDesc $value)
    {
        $this->emergency = $value;
        return $this;
    }

    public function hasVoicemail()
    {
        return $this->voicemail !== null;
    }

    /**
     * @return PhoneNumberDesc
     */
    public function getVoicemail()
    {
        return $this->voicemail;
    }

    public function setVoicemail(PhoneNumberDesc $value)
    {
        $this->voicemail = $value;
        return $this;
    }

    public function hasShortCode()
    {
        return $this->short_code !== null;
    }

    public function getShortCode()
    {
        return $this->short_code;
    }

    public function setShortCode(PhoneNumberDesc $value)
    {
        $this->short_code = $value;
        return $this;
    }

    public function hasStandardRate()
    {
        return $this->standard_rate !== null;
    }

    public function getStandardRate()
    {
        return $this->standard_rate;
    }

    public function setStandardRate(PhoneNumberDesc $value)
    {
        $this->standard_rate = $value;
        return $this;
    }

    public function hasCarrierSpecific()
    {
        return $this->carrierSpecific !== null;
    }

    public function getCarrierSpecific()
    {
        return $this->carrierSpecific;
    }

    public function setCarrierSpecific(PhoneNumberDesc $value)
    {
        $this->carrierSpecific = $value;
        return $this;
    }

    public function hasSmsServices()
    {
        return $this->smsServices !== null;
    }

    public function getSmsServices()
    {
        return $this->smsServices;
    }

    public function setSmsServices(PhoneNumberDesc $value)
    {
        $this->smsServices = $value;
        return $this;
    }

    public function hasNoInternationalDialling()
    {
        return $this->noInternationalDialling !== null;
    }

    public function getNoInternationalDialling()
    {
        return $this->noInternationalDialling;
    }

    public function setNoInternationalDialling(PhoneNumberDesc $value)
    {
        $this->noInternationalDialling = $value;
        return $this;
    }

    /**
     * @return string
     */
    public function getId()
    {
        return $this->id;
    }

    /**
     * @param string $value
     * @return PhoneMetadata
     */
    public function setId($value)
    {
        $this->id = $value;
        return $this;
    }

    /**
     * @return int
     */
    public function getCountryCode()
    {
        return $this->countryCode;
    }

    /**
     * @param int $value
     * @return PhoneMetadata
     */
    public function setCountryCode($value)
    {
        $this->countryCode = $value;
        return $this;
    }

    public function getInternationalPrefix()
    {
        return $this->internationalPrefix;
    }

    public function setInternationalPrefix($value)
    {
        $this->internationalPrefix = $value;
        return $this;
    }

    public function hasPreferredInternationalPrefix()
    {
        return ($this->preferredInternationalPrefix !== null);
    }

    public function getPreferredInternationalPrefix()
    {
        return $this->preferredInternationalPrefix;
    }

    public function setPreferredInternationalPrefix($value)
    {
        $this->preferredInternationalPrefix = $value;
        return $this;
    }

    public function clearPreferredInternationalPrefix()
    {
        $this->preferredInternationalPrefix = null;
        return $this;
    }

    public function hasNationalPrefix()
    {
        return $this->nationalPrefix !== null;
    }

    public function getNationalPrefix()
    {
        return $this->nationalPrefix;
    }

    public function setNationalPrefix($value)
    {
        $this->nationalPrefix = $value;
        return $this;
    }

    public function clearNationalPrefix()
    {
        $this->nationalPrefix = '';
        return $this;
    }

    public function hasPreferredExtnPrefix()
    {
        return $this->preferredExtnPrefix !== null;
    }

    public function getPreferredExtnPrefix()
    {
        return $this->preferredExtnPrefix;
    }

    public function setPreferredExtnPrefix($value)
    {
        $this->preferredExtnPrefix = $value;
        return $this;
    }

    public function clearPreferredExtnPrefix()
    {
        $this->preferredExtnPrefix = '';
        return $this;
    }

    public function hasNationalPrefixForParsing()
    {
        return $this->nationalPrefixForParsing !== null;
    }

    public function getNationalPrefixForParsing()
    {
        return $this->nationalPrefixForParsing;
    }

    public function setNationalPrefixForParsing($value)
    {
        $this->nationalPrefixForParsing = $value;
        return $this;
    }

    public function hasNationalPrefixTransformRule()
    {
        return $this->nationalPrefixTransformRule !== null;
    }

    public function getNationalPrefixTransformRule()
    {
        return $this->nationalPrefixTransformRule;
    }

    public function setNationalPrefixTransformRule($value)
    {
        $this->nationalPrefixTransformRule = $value;
        return $this;
    }

    public function clearNationalPrefixTransformRule()
    {
        $this->nationalPrefixTransformRule = '';
        return $this;
    }

    public function getSameMobileAndFixedLinePattern()
    {
        return $this->sameMobileAndFixedLinePattern;
    }

    public function setSameMobileAndFixedLinePattern($value)
    {
        $this->sameMobileAndFixedLinePattern = $value;
        return $this;
    }

    public function clearSameMobileAndFixedLinePattern()
    {
        $this->sameMobileAndFixedLinePattern = false;
        return $this;
    }

    /**
     * @return NumberFormat[]
     */
    public function numberFormats()
    {
        return $this->numberFormat;
    }

    public function intlNumberFormats()
    {
        return $this->intlNumberFormat;
    }

    /**
     * @return bool
     */
    public function hasLeadingDigits()
    {
        return $this->leadingDigits !== null;
    }

    public function getLeadingDigits()
    {
        return $this->leadingDigits;
    }

    public function setLeadingDigits($value)
    {
        $this->leadingDigits = $value;
        return $this;
    }

    public function isLeadingZeroPossible()
    {
        return $this->leadingZeroPossible;
    }

    public function setLeadingZeroPossible($value)
    {
        $this->leadingZeroPossible = $value;
        return $this;
    }

    public function clearLeadingZeroPossible()
    {
        $this->leadingZeroPossible = false;
        return $this;
    }

    public function isMobileNumberPortableRegion()
    {
        return $this->mobileNumberPortableRegion;
    }

    public function setMobileNumberPortableRegion($value)
    {
        $this->mobileNumberPortableRegion = $value;
        return $this;
    }

    public function clearMobileNumberPortableRegion()
    {
        $this->mobileNumberPortableRegion = false;
        return $this;
    }

    /**
     * @param array $input
     * @return PhoneMetadata
     */
    public function fromArray(array $input)
    {
        if (isset($input['generalDesc'])) {
            $desc = new PhoneNumberDesc();
            $this->setGeneralDesc($desc->fromArray($input['generalDesc']));
        }

        if (isset($input['fixedLine'])) {
            $desc = new PhoneNumberDesc();
            $this->setFixedLine($desc->fromArray($input['fixedLine']));
        }

        if (isset($input['mobile'])) {
            $desc = new PhoneNumberDesc();
            $this->setMobile($desc->fromArray($input['mobile']));
        }

        if (isset($input['tollFree'])) {
            $desc = new PhoneNumberDesc();
            $this->setTollFree($desc->fromArray($input['tollFree']));
        }

        if (isset($input['premiumRate'])) {
            $desc = new PhoneNumberDesc();
            $this->setPremiumRate($desc->fromArray($input['premiumRate']));
        }

        if (isset($input['sharedCost'])) {
            $desc = new PhoneNumberDesc();
            $this->setSharedCost($desc->fromArray($input['sharedCost']));
        }

        if (isset($input['personalNumber'])) {
            $desc = new PhoneNumberDesc();
            $this->setPersonalNumber($desc->fromArray($input['personalNumber']));
        }

        if (isset($input['voip'])) {
            $desc = new PhoneNumberDesc();
            $this->setVoip($desc->fromArray($input['voip']));
        }

        if (isset($input['pager'])) {
            $desc = new PhoneNumberDesc();
            $this->setPager($desc->fromArray($input['pager']));
        }

        if (isset($input['uan'])) {
            $desc = new PhoneNumberDesc();
            $this->setUan($desc->fromArray($input['uan']));
        }

        if (isset($input['emergency'])) {
            $desc = new PhoneNumberDesc();
            $this->setEmergency($desc->fromArray($input['emergency']));
        }

        if (isset($input['voicemail'])) {
            $desc = new PhoneNumberDesc();
            $this->setVoicemail($desc->fromArray($input['voicemail']));
        }

        if (isset($input['shortCode'])) {
            $desc = new PhoneNumberDesc();
            $this->setShortCode($desc->fromArray($input['shortCode']));
        }

        if (isset($input['standardRate'])) {
            $desc = new PhoneNumberDesc();
            $this->setStandardRate($desc->fromArray($input['standardRate']));
        }

        if (isset($input['carrierSpecific'])) {
            $desc = new PhoneNumberDesc();
            $this->setCarrierSpecific($desc->fromArray($input['carrierSpecific']));
        }

        if (isset($input['smsServices'])) {
            $desc = new PhoneNumberDesc();
            $this->setSmsServices($desc->fromArray($input['smsServices']));
        }

        if (isset($input['noInternationalDialling'])) {
            $desc = new PhoneNumberDesc();
            $this->setNoInternationalDialling($desc->fromArray($input['noInternationalDialling']));
        }

        $this->setId($input['id']);
        $this->setCountryCode($input['countryCode']);
        $this->setInternationalPrefix($input['internationalPrefix']);

        if (isset($input['preferredInternationalPrefix'])) {
            $this->setPreferredInternationalPrefix($input['preferredInternationalPrefix']);
        }
        if (isset($input['nationalPrefix'])) {
            $this->setNationalPrefix($input['nationalPrefix']);
        }
        if (isset($input['nationalPrefix'])) {
            $this->setNationalPrefix($input['nationalPrefix']);
        }

        if (isset($input['preferredExtnPrefix'])) {
            $this->setPreferredExtnPrefix($input['preferredExtnPrefix']);
        }

        if (isset($input['nationalPrefixForParsing'])) {
            $this->setNationalPrefixForParsing($input['nationalPrefixForParsing']);
        }

        if (isset($input['nationalPrefixTransformRule'])) {
            $this->setNationalPrefixTransformRule($input['nationalPrefixTransformRule']);
        }

        foreach ($input['numberFormat'] as $numberFormatElt) {
            $numberFormat = new NumberFormat();
            $numberFormat->fromArray($numberFormatElt);
            $this->addNumberFormat($numberFormat);
        }

        foreach ($input['intlNumberFormat'] as $intlNumberFormatElt) {
            $numberFormat = new NumberFormat();
            $numberFormat->fromArray($intlNumberFormatElt);
            $this->addIntlNumberFormat($numberFormat);
        }

        $this->setMainCountryForCode($input['mainCountryForCode']);

        if (isset($input['leadingDigits'])) {
            $this->setLeadingDigits($input['leadingDigits']);
        }

        if (isset($input['leadingZeroPossible'])) {
            $this->setLeadingZeroPossible($input['leadingZeroPossible']);
        }

        if (isset($input['mobileNumberPortableRegion'])) {
            $this->setMobileNumberPortableRegion($input['mobileNumberPortableRegion']);
        }

        return $this;
    }

    public function addNumberFormat(NumberFormat $value)
    {
        $this->numberFormat[] = $value;
        return $this;
    }

    public function addIntlNumberFormat(NumberFormat $value)
    {
        $this->intlNumberFormat[] = $value;
        return $this;
    }
}
