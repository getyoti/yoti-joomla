<?php
/**
 * @var $this AdminYotiViewYoti
 */
?>
<div class="row-fluid">
    <div class="span12">
        <form id="yoti-form" method="post" enctype="multipart/form-data" action="<?php echo $_SERVER['REQUEST_URI']; ?>" class="form-horizontal">
            <p>
                <?php echo JText::_("You need to first create a Yoti App at"); ?> <a href="<?php echo \Yoti\YotiClient::DASHBOARD_URL; ?>" target="_blank">
                    <?php echo JText::_("Yoti Dashboard"); ?>
                </a>.
            </p>
            <p>
                <?php echo JText::_("Note: On the Yoti Dashboard the callback URL should be set to:"); ?>
                <code><?php echo JUri::root(false).'index.php?option=com_yoti&task=login'; ?></code>
            </p>
            <div class="control-group">
                <label for="yoti_app_id" class="col-md-6 control-label"><?php echo JText::_("App ID"); ?> <span><strong>*</strong></span></label>
                <div class="controls">
                    <input type="text" name="yoti_app_id" id="yoti_app_id" placeholder="Yoti App ID"
                           value="<?php if (!empty($this->data['yoti_app_id'])) echo htmlspecialchars($this->data['yoti_app_id']); ?>"
                           class="form-control input-xlarge"
                           required="true"
                    />
                    <span class="help-block"><?php echo JText::_("Copy the App ID from your Yoti App here"); ?></span>
                </div>
            </div>
            <div class="control-group">
                <label for="yoti_scenario_id" class="col-md-6 control-label"><?php echo JText::_("Scenario ID"); ?> <span><strong>*</strong></span></label>
                <div class="controls">
                    <input type="text" name="yoti_scenario_id" id="yoti_scenario_id" placeholder="Yoti Scenario ID"
                           value="<?php if (!empty($this->data['yoti_scenario_id'])) echo htmlspecialchars($this->data['yoti_scenario_id']); ?>"
                           class="form-control input-xlarge"
                           required="true"
                    />
                    <span class="help-block">
                        <?php echo JText::_("Scenario ID is used to render the inline QR code"); ?>
                    </span>
                </div>
            </div>
            <div class="control-group">
                <label for="yoti_sdk_id" class="col-md-6 control-label">
                    <?php echo JText::_("SDK ID"); ?> <span><strong>*</strong></span>
                </label>
                <div class="controls">
                    <input type="text" name="yoti_sdk_id" id="yoti_sdk_id" placeholder="Yoti SDK ID"
                           value="<?php if (!empty($this->data['yoti_sdk_id'])) echo htmlspecialchars($this->data['yoti_sdk_id']); ?>"
                           class="form-control input-xlarge"
                           required="true"
                    />
                    <span class="help-block"><?php echo JText::_("Copy the SDK ID from your Yoti App here"); ?></span>
                </div>
            </div>
            <div class="control-group">
                <label for="yoti_company_name" class="col-md-6 control-label"><?php echo JText::_("Company Name"); ?></label>
                <div class="controls">
                    <input type="text" name="yoti_company_name" id="yoti_company_name" placeholder="Company Name"
                           value="<?php if (!empty($this->data['yoti_company_name'])) echo htmlspecialchars($this->data['yoti_company_name']); ?>"
                           class="form-control input-xlarge"
                    />
                    <span class="help-block"><?php echo JText::_("To tailor our Yoti plugin please add your company name"); ?></span>
                </div>
            </div>
            <div class="control-group">
                <label for="yoti_success_url" class="col-md-6 control-label">
                    <?php echo JText::_("Success URL"); ?> <span><strong>*</strong></span>
                </label>
                <div class="controls">
                    <input type="text" name="yoti_success_url" id="yoti_success_url" placeholder="Success URL"
                           value="<?php echo $this->successUrl; ?>"
                           class="form-control input-xlarge"
                           required="true"
                    />
                    <span class="help-block"><?php echo JText::_("Redirect users here if they successfully login with Yoti"); ?></span>
                </div>
            </div>
            <div class="control-group">
                <label for="yoti_failed_url" class="col-md-6 control-label">
                    <?php echo JText::_("Failed URL"); ?> <span><strong>*</strong></span>
                </label>
                <div class="controls">
                    <input type="text" name="yoti_failed_url" id="yoti_failed_url" placeholder="Failed URL"
                           value="<?php echo $this->failedUrl; ?>"
                           class="form-control input-xlarge"
                           required="true"
                    />
                    <span class="help-block">
                        <?php echo JText::_("Redirect users here if they were unable to login with Yoti"); ?>
                    </span>
                </div>
            </div>
            <div class="control-group">
                <label for="yoti_pem" class="col-md-6 control-label">
                    <?php echo JText::_("PEM File"); ?> <span><strong>*</strong></span>
                </label>
                <div class="controls">
                    <?php
                    $pemFileRequired = 'required="true"';
                    if (!empty($this->data['yoti_pem.name']))
                    {
                        $pemFileRequired = '';
                        echo '<div class="pem-file">' .
                            '<code><strong>' . JText::_("Current file") . ':</strong> ' . htmlspecialchars($this->data['yoti_pem.name']) . '</code>' .
                            '<label class="checkbox"><input type="checkbox" name="yoti_delete_pem" value="1"' . $this->pemFilechecked . ' />' .
                            JText::_("Delete this PEM file") . '</label>' .
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
                        <?php echo JText::_("Only allow existing Joomla users to link their Yoti account"); ?>
                    </label>
                </div>
            </div>
            <div class="control-group">
                <div class="controls">
                    <label class="checkbox"><input type="checkbox" name="yoti_user_email" value="1"
                            <?php echo $this->userEmailChecked ?>
                        />
                        <?php echo JText::_("Attempt to link Yoti email address with Joomla user account for first time users"); ?>
                    </label>
                </div>
            </div>
            <div class="btn-wrapper controls">
                <button class="btn btn-small btn-success">
                    <span class="icon-apply icon-white"></span>
                    <?php echo JText::_("Save Settings"); ?>
                </button>
            </div>
        </form>
    </div>
</div>