<?php
/**
 * @var $this AdminYotiConnectViewYotiConnect
 */
?>
<div class="row-fluid">
    <div class="span12">
        <form id="yoti-connect-form" method="post" enctype="multipart/form-data" action="<?php echo $_SERVER['REQUEST_URI']; ?>" class="form-horizontal">
            <p>You need to first create a Yoti App at <a href="<?php echo \Yoti\YotiClient::DASHBOARD_URL; ?>" target="_blank">Yoti Dashboard</a>.</p>
            <p>Note: On the Yoti Dashboard the callback URL should be set to: <code><?php echo JUri::root(false).'index.php?option=com_yoticonnect&task=login'; ?></code></p>
            <div class="control-group">
                <label for="yoti_app_id" class="col-md-6 control-label">Yoti App ID</label>
                <div class="controls">
                    <input type="text" name="yoti_app_id" id="yoti_app_id" placeholder="Yoti App ID" value="<?php if (!empty($this->data['yoti_app_id'])) echo htmlspecialchars($this->data['yoti_app_id']); ?>" class="form-control input-xlarge" />
                </div>
            </div>
            <div class="control-group">
                <label for="yoti_sdk_id" class="col-md-6 control-label">Yoti SDK ID</label>
                <div class="controls">
                    <input type="text" name="yoti_sdk_id" id="yoti_sdk_id" placeholder="Yoti SDK ID" value="<?php if (!empty($this->data['yoti_sdk_id'])) echo htmlspecialchars($this->data['yoti_sdk_id']); ?>" class="form-control input-xlarge" />
                </div>
            </div>

            <div class="control-group">
                <label for="yoti_pem" class="col-md-6 control-label">Yoti PEM File</label>
                <div class="controls">
                    <?php
                    if (!empty($this->data['yoti_pem.name']))
                    {
                        $checked = (!empty($this->data['yoti_delete_pem']) ? ' checked="checked"' : '');
                        echo '<div class="pem-file">' .
                            '<code><strong>Current file:</strong> ' . htmlspecialchars($this->data['yoti_pem.name']) . '</code>' .
                            '<label class="checkbox"><input type="checkbox" name="yoti_delete_pem" value="1"' . $checked . ' /> Delete this PEM file</label>' .
                            '</div>';
                    }
                    ?>
                    <input type="file" name="yoti_pem" id="yoti_pem" class="form-control input-xlarge" />
                </div>
            </div>
            <div class="btn-wrapper controls">
                <button class="btn btn-small btn-success">
                    <span class="icon-apply icon-white"></span>
                    Save Settings
                </button>
            </div>
        </form>
    </div>
</div>