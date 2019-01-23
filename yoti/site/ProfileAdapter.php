<?php
namespace YotiJoomla;

use Yoti\Entity\Profile;

class ProfileAdapter
{
    /**
     * @var array
     */
    private $attributes;

    /**
     * @var int
     */
    private $joomlaUserId;

    public function __construct(array $profileAttrs, $joomlaUserIdId = NULL)
    {
        $this->joomlaUserId = $joomlaUserIdId;
        $this->setAttributes($profileAttrs);
    }

    /**
     * @return null|string
     */
    public function getFullName()
    {
        return $this->getProfileAttribute(Profile::ATTR_FULL_NAME);
    }

    public function getGivenNames()
    {
        return $this->getProfileAttribute(Profile::ATTR_GIVEN_NAMES);
    }

    /**
     * @return null|string
     */
    public function getFamilyName()
    {
        return $this->getProfileAttribute(Profile::ATTR_FAMILY_NAME);
    }

    /**
     * @return null|string
     */
    public function getDateOfBirth()
    {
        return $this->getProfileAttribute(Profile::ATTR_DATE_OF_BIRTH);
    }

    /**
     * @return null|string
     */
    public function getGender()
    {
        return $this->getProfileAttribute(Profile::ATTR_GENDER);
    }

    /**
     * @return null|string
     */
    public function getNationality()
    {
        return $this->getProfileAttribute(Profile::ATTR_NATIONALITY);
    }

    /**
     * @return null|string
     */
    public function getPhoneNumber()
    {
        return $this->getProfileAttribute(Profile::ATTR_PHONE_NUMBER);
    }

    /**
     * @return null|string
     */
    public function getSelfie()
    {
        return $this->getProfileAttribute(Profile::ATTR_SELFIE);
    }

    /**
     * @return null|string
     */
    public function getEmailAddress()
    {
        return $this->getProfileAttribute(Profile::ATTR_EMAIL_ADDRESS);
    }

    /**
     * Return postal_address or structured_postal_address.formatted_address.
     *
     * @return null|string
     */
    public function getPostalAddress()
    {
        return $this->getProfileAttribute(Profile::ATTR_POSTAL_ADDRESS);
    }

    public function getAgeVerifications()
    {
        return $this->getProfileAttribute(Profile::ATTR_AGE_VERIFICATIONS);
    }

    public function getYotiUserId()
    {
        return $this->getProfileAttribute(ActivityDetailsAdapter::YOTI_USER_ID);
    }

    public function getProfileAttribute($attrName)
    {
        return isset($this->attributes[$attrName]) ? $this->attributes[$attrName] : NULL;
    }

    private function setAttributes(array $profileAttrs)
    {
        $this->attributes = $profileAttrs;
    }
}