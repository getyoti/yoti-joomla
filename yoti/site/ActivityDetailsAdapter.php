<?php
namespace YotiJoomla;

use Yoti\ActivityDetails;
use Yoti\Entity\Profile;
use Yoti\Entity\AgeVerification;

require_once JPATH_ROOT . '/components/com_yoti/ProfileAdapter.php';

class ActivityDetailsAdapter
{
    const YOTI_USER_ID = 'yoti_user_id';
    /**
     * @var ProfileAdapter
     */
    private $profile;

    /**
     * @var string
     */
    private $rememberMeId;


    /**
     * ActivityDetailsAdapter constructor.
     *
     * @param array $profileData
     * @param int $userId
     */
    public function __construct(ActivityDetails $activityDetails)
    {
        $this->rememberMeId = $activityDetails->getRememberMeId();

        $this->setProfile($activityDetails->getProfile());
    }

    public function getProfile()
    {
        return $this->profile;
    }

    public function getRememberMeId()
    {
        return $this->rememberMeId;
    }

    private function setProfile(Profile $profile)
    {
        $attributesArr = [];
        $excludedAttrs = $this->getExcludedAttributes();

        foreach($profile->getAttributes() as $attrName => $attrObj) {
            if (in_array($attrName, $excludedAttrs)) {
                continue;
            }
            $attrValue = $attrObj ? $attrObj->getValue() : NULL;

            if ($attrName === Profile::ATTR_DATE_OF_BIRTH && $attrValue !== NULL) {
                $attrValue = $attrValue->format('d-m-Y');
            }
            if ($attrName === Profile::ATTR_SELFIE && NULL !== $attrValue) {
                $attrValue = $attrValue->getContent();
            }
            $attributesArr[$attrName] = $attrValue;
        }
        $ageVerificationsArr = $this->getAgeVerificationsData($profile);

        $attributesArr = array_merge(
            $attributesArr,
            [Profile::ATTR_AGE_VERIFICATIONS => $ageVerificationsArr],
            [self::YOTI_USER_ID => $this->rememberMeId]
        );

        $this->profile = new ProfileAdapter($attributesArr);
    }

    private function getExcludedAttributes()
    {
        return [
            Profile::ATTR_DOCUMENT_DETAILS,
            Profile::ATTR_STRUCTURED_POSTAL_ADDRESS
        ];
    }

    private function getAgeVerificationsData(Profile $profile)
    {
        $ageVerificationsArr = [];
        /**
         * @var AgeVerification $ageVerification
         */
        foreach($profile->getAgeVerifications() as $ageAttr => $ageVerification) {
            $attr = str_replace(':', '_', ucwords($ageAttr,'_'));
            if ($ageVerification instanceof AgeVerification) {
                $ageVerificationsArr[] = [$attr => $ageVerification->getResult() ? 'Yes' : 'No'];
            }
        }
        return $ageVerificationsArr;
    }
}