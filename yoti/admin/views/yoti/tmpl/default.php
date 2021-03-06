<?php
/**
 * @var $this AdminYotiViewYoti
 * @license     GNU General Public License version 3; see LICENSE.txt
 */
defined('_JEXEC') or die('Restricted access');
?>
<div class="row-fluid">
    <div class="span12">
        <form id="yoti-form" method="post" enctype="multipart/form-data" action="<?php echo $_SERVER['REQUEST_URI']; ?>" class="form-horizontal">
            <?php echo JHTML::_('form.token'); ?>
            <div class="alert">
            <p>
                <?php echo JText::_('You need to first create a Yoti App at'); ?> <a href="<?php echo YotiHelper::YOTI_HUB_URL; ?>" target="_blank">
                    <?php echo JText::_('Yoti Hub'); ?>
                </a>.
            </p>
            <p>
                <?php echo JText::_('Note: On the Yoti Hub the callback URL should be set to:'); ?>
                <strong><?php echo JUri::root(false).'index.php?option=com_yoti&task=login'; ?></strong>
            </p>
            <p>
                <?php echo JText::_('Warning: User IDs provided by Yoti are only valid within the scope of the application. Changing your Yoti application will result in different Yoti user IDs.'); ?>
            </p>
            </div>
            <div class="control-group">
                <label for="yoti_app_id" class="col-md-6 control-label"><?php echo JText::_("App ID"); ?> <span><strong>*</strong></span></label>
                <div class="controls">
                    <input type="text" name="yoti_app_id" id="yoti_app_id" placeholder="Yoti App ID"
                           value="<?php echo !empty($this->data['yoti_app_id']) ? htmlspecialchars($this->data['yoti_app_id']) : ''; ?>"
                           class="form-control input-xlarge"
                           required="true"
                    />
                    <span class="help-block"><?php echo JText::_('Copy the App ID from your Yoti App here'); ?></span>
                </div>
            </div>
            <div class="control-group">
                <label for="yoti_scenario_id" class="col-md-6 control-label"><?php echo JText::_('Scenario ID'); ?> <span><strong>*</strong></span></label>
                <div class="controls">
                    <input type="text" name="yoti_scenario_id" id="yoti_scenario_id" placeholder="Yoti Scenario ID"
                           value="<?php echo !empty($this->data['yoti_scenario_id']) ? htmlspecialchars($this->data['yoti_scenario_id']) : ''; ?>"
                           class="form-control input-xlarge"
                           required="true"
                    />
                    <span class="help-block">
                        <?php echo JText::_('Scenario ID identifies the attributes associated with your Yoti application. This value can be found on your application page in Yoti Hub.'); ?>
                    </span>
                </div>
            </div>
            <div class="control-group">
                <label for="yoti_sdk_id" class="col-md-6 control-label">
                    <?php echo JText::_('Client SDK ID'); ?> <span><strong>*</strong></span>
                </label>
                <div class="controls">
                    <input type="text" name="yoti_sdk_id" id="yoti_sdk_id" placeholder="Yoti Client SDK ID"
                           value="<?php echo !empty($this->data['yoti_sdk_id']) ? htmlspecialchars($this->data['yoti_sdk_id']) : ''; ?>"
                           class="form-control input-xlarge"
                           required="true"
                    />
                    <span class="help-block"><?php echo JText::_('Client SDK ID identifies your Yoti Hub application. This value can be found in the Hub, within your application section, in the keys tab.'); ?></span>
                </div>
            </div>
            <div class="control-group">
                <label for="yoti_company_name" class="col-md-6 control-label"><?php echo JText::_('Company Name'); ?></label>
                <div class="controls">
                    <input type="text" name="yoti_company_name" id="yoti_company_name" placeholder="Company Name"
                           value="<?php echo !empty($this->data['yoti_company_name']) ? htmlspecialchars($this->data['yoti_company_name']) : ''; ?>"
                           class="form-control input-xlarge"
                    />
                    <span class="help-block"><?php echo JText::_('To tailor the login form messages please add your company name'); ?></span>
                </div>
            </div>
            <div class="control-group">
                <label for="yoti_success_url" class="col-md-6 control-label">
                    <?php echo JText::_('Success URL'); ?> <span><strong>*</strong></span>
                </label>
                <div class="controls">
                    <input type="text" name="yoti_success_url" id="yoti_success_url" placeholder="Success URL"
                           value="<?php echo $this->successUrl; ?>"
                           class="form-control input-xlarge"
                           required="true"
                    />
                    <span class="help-block"><?php echo JText::_('Redirect users here if they successfully login with Yoti'); ?></span>
                </div>
            </div>
            <div class="control-group">
                <label for="yoti_failed_url" class="col-md-6 control-label">
                    <?php echo JText::_('Failed URL'); ?> <span><strong>*</strong></span>
                </label>
                <div class="controls">
                    <input type="text" name="yoti_failed_url" id="yoti_failed_url" placeholder="Failed URL"
                           value="<?php echo $this->failedUrl; ?>"
                           class="form-control input-xlarge"
                           required="true"
                    />
                    <span class="help-block">
                        <?php echo JText::_('Redirect users here if they were unable to login with Yoti'); ?>
                    </span>
                </div>
            </div>
            <div class="control-group">
                <label for="yoti_pem" class="col-md-6 control-label">
                    <?php echo JText::_('PEM File'); ?> <span><strong>*</strong></span>
                </label>
                <div class="controls">
                    <?php
                    $pemFileRequired = 'required="true"';
                    if (!empty($this->data['yoti_pem.name'])) {
                        $pemFileRequired = '';
                        echo '<div class="pem-file">' .
                            '<span class="alert alert-no-item"><strong>' . JText::_('Current file') . ':</strong> ' . htmlspecialchars($this->data['yoti_pem.name']) . '</span>' .
                            '<label class="checkbox"><input type="checkbox" name="yoti_delete_pem" value="1"' . $this->pemFilechecked . ' />' .
                            JText::_('Delete this PEM file') . '</label>' .
                            '</div>';
                    }
                    ?>
                    <input type="file" name="yoti_pem" id="yoti_pem" class="form-control input-xlarge" <?php echo $pemFileRequired; ?>/>
                </div>
            </div>
            <div class="control-group">
                <div class="controls">
                    <label class="checkbox"><input type="checkbox" name="yoti_only_existing_user" value="1"
                            <?php echo $this->onlyExitingUserChecked ?>
                        />
                        <?php echo JText::_('Only allow existing Joomla users to link their Yoti account'); ?>
                    </label>
                </div>
            </div>
            <div class="control-group">
                <div class="controls">
                    <label class="checkbox"><input type="checkbox" name="yoti_user_email" value="1"
                            <?php echo $this->userEmailChecked ?>
                        />
                        <?php echo JText::_('Attempt to link Yoti email address with Joomla user account for first time users'); ?>
                    </label>
                </div>
            </div>
            <div class="control-group">
                <div class="controls">
                    <label class="checkbox"><input type="checkbox" name="yoti_age_verification" value="1"
                            <?php echo $this->ageVerificationChecked ?>
                        />
                        <?php echo JText::_('Prevent users who have not passed age verification to access your site'); ?>
                    </label>
                    <span class="help-block">
                        <?php echo JText::_('(Requires Age verify condition to be set in the Yoti Hub)'); ?>
                    </span>
                </div>
            </div>
            <div class="btn-wrapper controls">
                <button class="btn btn-small btn-success">
                    <span class="icon-apply icon-white"></span>
                    <?php echo JText::_('Save Settings'); ?>
                </button>
            </div>
        </form>
    </div>
</div>