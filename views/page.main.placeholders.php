<?php if (!defined('FREEPBX_IS_AUTH')) { die('No direct script access allowed'); } ?>

<?= $endpoint_warn ?>
<script>
    var $hwgrid = $('#hwgrid');
    var mydata = 
    [
        {
            "type": "<?= _('Line Loop')?>",
            "brand": "<?= _('Global')?>",
            "placeholder": "$globaladminpassword",
            "description": "<?= _('Global Admin Password')?>"
        },
        {
            "type": "<?= _('Static')?>",
            "brand": "<?= _('Global')?>",
            "placeholder": "$globaladminpassword",
            "description": "<?= _('Global Admin Password')?>"
        },
        {
            "type": "<?= _('Line Loop')?>",
            "brand": "<?= _('Global')?>",
            "placeholder": "$globaluserpassword",
            "description": "<?= _('Global User Password')?>"
        },
        {
            "type": "<?= _('Static')?>",
            "brand": "<?= _('Global')?>",
            "placeholder": "$globaluserpassword",
            "description": "<?= _('Global User Password')?>"
        },
        {
            "type": "<?= _('Static')?>",
            "brand": "<?= _('Global')?>",
            "placeholder": "$provisuser",
            "description": "<?= _('Sysadmin Pro Provisioning HTTP User')?>"
        },
        {
            "type": "<?= _('Line Loop')?>",
            "brand": "<?= _('Global')?>",
            "placeholder": "$provisuser",
            "description": "<?= _('Sysadmin Pro Provisioning HTTP User')?>"
        },
        {
            "type": "<?= _('Static')?>",
            "brand": "<?= _('Global')?>",
            "placeholder": "$provispass",
            "description": "<?= _('Sysadmin Pro Provisioning HTTP Password')?>"
        },
        {
            "type": "<?= _('Line Loop')?>",
            "brand": "<?= _('Global')?>",
            "placeholder": "$provispass",
            "description": "<?= _('Sysadmin Pro Provisioning HTTP Password')?>"
        },
        {
            "type": "<?= _('Static')?>",
            "brand": "<?= _('Global')?>",
            "placeholder": "$sslhpro",
            "description": "<?= _('Sysadmin Pro Provisioning HTTPS Port')?>"
        },
        {
            "type": "<?= _('Line Loop')?>",
            "brand": "<?= _('Global')?>",
            "placeholder": "$sslhpros",
            "description": "<?= _('Sysadmin Pro Provisioning HTTPS Port')?>"
        },
        {
            "type": "<?= _('Static')?>",
            "brand": "<?= _('Global')?>",
            "placeholder": "$hpro",
            "description": "<?= _('Sysadmin Pro Provisioning HTTP Port')?>"
        },
        {
            "type": "<?= _('Line Loop')?>",
            "brand": "<?= _('Global')?>",
            "placeholder": "$hpro",
            "description": "<?= _('Sysadmin Pro Provisioning HTTP Port')?>"
        },
        {
            "type": "<?= _('Line Loop')?>",
            "brand": "<?= _('Global')?>",
            "placeholder": "$line",
            "description": "<?= _('Prints the line Number of the mapped extension')?>"
        },
        {
            "type": "<?= _('Line Loop')?>",
            "brand": "<?= _('Global')?>",
            "placeholder": "$username",
            "description": "<?= _('Username for the Extension (most likely the endpoint extension number)')?>"
        },
        {
            "type": "<?= _('Line Loop')?>",
            "brand": "<?= _('Global')?>",
            "placeholder": "$authname",
            "description": "<?= _('Auth name for the Extension (most likely the endpoint extension number)')?>"
        },
        {
            "type": "<?= _('Line Loop')?>",
            "brand": "<?= _('Yealink')?>",
            "placeholder": "$yealinktransport",
            "description": "<?= _('Transport protocoll for Yealink (UDP,TCP,TLS)')?>"
        },
        {
            "type": "<?= _('Static')?>",
            "brand": "<?= _('Yealink')?>",
            "placeholder": "$accXyealinktransport",
            "description": "<?= _('Transport protocoll for Yealink (UDP,TCP,TLS)')?>"
        },
        {
            "type": "<?= _('Line Loop')?>",
            "brand": "<?= _('Yealink')?>",
            "placeholder": "$yealinksrtp",
            "description": "<?= _('SRTP Value for Yealink')?>"
        },
        {
            "type": "<?= _('Static')?>",
            "brand": "<?= _('Yealink')?>",
            "placeholder": "$accXyealinksrtp",
            "description": "<?= _('SRTP Value for Yealink')?>"
        },
        {
            "type": "<?= _('Line Loop')?>",
            "brand": "<?= _('Global')?>",
            "placeholder": "$secret",
            "description": "<?= _('Password for the mapped Extension')?>"
        },	
        {
            "type": "<?= _('Line Loop')?>",
            "brand": "<?= _('Global')?>",
            "placeholder": "$displayname",
            "description": "<?= _('Display name for the Extension (The Name of the Extension')?>"
        },	
        {
            "type": "<?= _('Line Loop')?>",
            "brand": "<?= _('Global')?>",
            "placeholder": "$server_host",
            "description": "<?= _('Server Hostname for the Extension (You can set your Hostname in your global settings)')?>"
        },
        {
            "type": "<?= _('Line Loop')?>",
            "brand": "<?= _('Global')?>",
            "placeholder": "$server_port",
            "description": "<?= _('The port your extension uses to connect. (The prefered Port you set in your extension will be used)')?>"
        },
        {
            "type": "<?= _('Line Loop')?>",
            "brand": "<?= _('Global')?>",
            "placeholder": "$proto",
            "description": "<?= _('Shows your Protocol your extension is using')?>"
        },
        {
            "type": "<?= _('Line Loop')?>",
            "brand": "<?= _('Global')?>",
            "placeholder": "$user_extension",
            "description": "<?= _('Shows your Extension number you are using')?>"
        },
        {
            "type": "<?= _('Line Loop')?>",
            "brand": "<?= _('Global')?>",
            "placeholder": "$extension",
            "description": "<?= _('Shows your Extension number you are using')?>"
        },
        {
            "type": "<?= _('Line Loop')?>",
            "brand": "<?= _('Global')?>",
            "placeholder": "$allowedcodec",
            "description": "<?= _('Prints you allowed codecs you set in your extension settings')?>"
        },
        {
            "type": "<?= _('Line Loop')?>",
            "brand": "<?= _('Global')?>",
            "placeholder": "$forcerport",
            "description": "<?= _('Shows you if you are using rport')?>"
        },
        {
            "type": "<?= _('Line Loop')?>",
            "brand": "<?= _('Global')?>",
            "placeholder": "$media_encryption",
            "description": "<?= _('Shows you if you have enabled media encryption')?>"
        },
        {
            "type": "<?= _('Line Loop')?>",
            "brand": "<?= _('Global')?>",
            "placeholder": "$sipdriver",
            "description": "<?= _('Shows you if you use SIP or PJSIP')?>"
        },
        {
            "type": "<?= _('Line Loop')?>",
            "brand": "<?= _('Global')?>",
            "placeholder": "$transport",
            "description": "<?= _('Shows you your prefered transport protocol')?>"
        },
        {
            "type": "<?= _('Line Loop')?>",
            "brand": "<?= _('Global')?>",
            "placeholder": "$trustrpid",
            "description": "<?= _('Trustrpid?')?>"
        },
        {
            "type": "<?= _('Line Loop')?>",
            "brand": "<?= _('Global')?>",
            "placeholder": "$callerid",
            "description": "<?= _('Prints your full callerid including name and number')?>"
        },
        {
            "type": "<?= _('Line Loop')?>",
            "brand": "<?= _('Global')?>",
            "placeholder": "$encryption",
            "description": "<?= _('Shows you if you have enabled encryption')?>"
        },
        {
            "type": "<?= _('Line Loop')?>",
            "brand": "<?= _('Global')?>",
            "placeholder": "$tech",
            "description": "<?= _('Shows you if you use SIP or PJSIP')?>"
        },
        {
            "type": "<?= _('Static')?>",
            "brand": "<?= _('Global')?>",
            "placeholder": "$accXsecret",
            "description": "<?= _('Password for the mapped Extension (change the X after acc with your line number)')?>"
        },
        {
            "type": "<?= _('Static')?>",
            "brand": "<?= _('Global')?>",
            "placeholder": "$accXdisplayname",
            "description": "<?= _('Display name for the Extension (The Name of the Extension (change the X after acc with your line number)')?>"
        },
        {
            "type": "<?= _('Static')?>",
            "brand": "<?= _('Global')?>",
            "placeholder": "$accXuser_extension",
            "description": "<?= _('Shows your Extension number you are using (change the X after acc with your line number)')?>"
        },
        {
            "type": "<?= _('Static')?>",
            "brand": "<?= _('Global')?>",
            "placeholder": "$accXusername",
            "description": "<?= _('Username for the Extension (change the X after acc with your line number)')?>"
        },
        {
            "type": "<?= _('Static')?>",
            "brand": "<?= _('Global')?>",
            "placeholder": "$accXauthname",
            "description": "<?= _('Auth name for the Extension (change the X after acc with your line number)')?>"
        },
        {
            "type": "<?= _('Static')?>",
            "brand": "<?= _('Global')?>",
            "placeholder": "$accXextension",
            "description": "<?= _('Shows your Extension number you are using (change the X after acc with your line number)')?>"
        },
        {
            "type": "<?= _('Static')?>",
            "brand": "<?= _('Global')?>",
            "placeholder": "$accXallowedcodec",
            "description": "<?= _('Prints you allowed codecs you set in your extension settings (change the X after acc with your line number)')?>"
        },
        {
            "type": "<?= _('Static')?>",
            "brand": "<?= _('Global')?>",
            "placeholder": "$accXforcerport",
            "description": "<?= _('Shows you if you are using rport (change the X after acc with your line number)')?>"
        },
        {
            "type": "<?= _('Static')?>",
            "brand": "<?= _('Global')?>",
            "placeholder": "$accXmedia_encryption",
            "description": "<?= _('Shows you if you have enabled media encryption (change the X after acc with your line number)')?>"
        },
        {
            "type": "<?= _('Static')?>",
            "brand": "<?= _('Global')?>",
            "placeholder": "$accXsipdriver",
            "description": "<?= _('Shows you if you use SIP or PJSIP (change the X after acc with your line number)')?>"
        },
        {
            "type": "<?= _('Static')?>",
            "brand": "<?= _('Global')?>",
            "placeholder": "$accXtransport",
            "description": "<?= _('Shows you your prefered transport protocol (change the X after acc with your line number)')?>"
        },
        {
            "type": "<?= _('Static')?>",
            "brand": "<?= _('Global')?>",
            "placeholder": "$accXtrustrpid",
            "description": "<?= _('Trustrpid? (change the X after acc with your line number)')?>"
        },
        {
            "type": "<?= _('Static')?>",
            "brand": "<?= _('Global')?>",
            "placeholder": "$accXcallerid",
            "description": "<?= _('Prints your full callerid including name and number (change the X after acc with your line number)')?>"
        },
        {
            "type": "<?= _('Static')?>",
            "brand": "<?= _('Global')?>",
            "placeholder": "$accXencryption",
            "description": "<?= _('Shows you if you have enabled encryption (change the X after acc with your line number)')?>"
        },
        {
            "type": "<?= _('Static')?>",
            "brand": "<?= _('Global')?>",
            "placeholder": "$accXserver_port",
            "description": "<?= _('The port your extension uses to connect. The prefered Port you set in your extension will be used. (change the X after acc with your line number)')?>"
        },
        {
            "type": "<?= _('Static')?>",
            "brand": "<?= _('Grandstream')?>",
            "placeholder": "$accXgsSRTP",
            "description": "<?= _('This is the SRTP Value (0/1) for Grandstream, tested with HT812 (change the X after acc with your line number)')?>"
        },
        {
            "type": "<?= _('Static')?>",
            "brand": "<?= _('Grandstream')?>",
            "placeholder": "$accXgsproto",
            "description": "<?= _('Sets the Protocol you set in you extension (TCP,UDP,TLS - Values:0,1,2),tested with HT812 (change the X after acc with your line number)')?>"
        },
        {
            "type": "<?= _('Line Loop')?>",
            "brand": "<?= _('Grandstream')?>",
            "placeholder": "$gsSRTP",
            "description": "<?= _('This is the SRTP Value (0/1) for Grandstream, tested with HT812')?>"
        },
        {
            "type": "<?= _('Line Loop')?>",
            "brand": "<?= _('Grandstream')?>",
            "placeholder": "$gsproto",
            "description": "<?= _('Sets the Protocol you set in you extension (TCP,UDP,TLS - Values:0,1,2),tested with HT812')?>"
        },
        {
            "type": "<?= _('Line Loop')?>",
            "brand": "<?= _('Global')?>",
            "placeholder": "$primtimeserver",
            "description": "<?= _('Hostname of the Primary NTP Server from Global Settings')?>"
        },
        {
            "type": "<?= _('Static')?>",
            "brand": "<?= _('Global')?>",
            "placeholder": "$primtimeserver",
            "description": "<?= _('Hostname of the Primary NTP Server from Global Settings')?>"
        },
        {
            "type": "<?= _('Line Loop')?>",
            "brand": "<?= _('Global')?>",
            "placeholder": "$myvoicemail",
            "description": "<?= _('Number of the Voicemail from Featurecodes')?>"
        },
        {
            "type": "<?= _('Static')?>",
            "brand": "<?= _('Global')?>",
            "placeholder": "$myvoicemail",
            "description": "<?= _('Number of the Voicemail from Featurecodes')?>"
        }
        
    ];

    function copyButtonFormatter(value, row, index)
    {
        return `<button class="btn btn-outline-primary btn-lg btn-block btn-copy" data-text="${value}">${value} <i class="fa fa-clipboard fa-pull-right" aria-hidden="true"></i></button>`;
    }

    document.addEventListener('DOMContentLoaded', function () {
        const table = document.getElementById('hwgrid');
        
        table.addEventListener('click', function (event) {
            if (event.target && event.target.matches('.btn-copy')) {
                const textToCopy = event.target.getAttribute('data-text');
                copyToClipboard(textToCopy);
            }
        });

        function copyToClipboard(text) {
            const tempInput = document.createElement('input');
            document.body.appendChild(tempInput);
            tempInput.value = text;
            tempInput.select();
            document.execCommand('copy');
            document.body.removeChild(tempInput);
            fpbxToast('<?= _("Copy To Clipboard") ?>', '', 'success');
        }
    });

    $(function () {
        $('#hwgrid').bootstrapTable({
            data: mydata
        });
    });
</script>

<div class="container-fluid">
    <h2><?= _('Config File Placeholder Values') ?></h2>
    <div class="fpbx-container">
        <div class="alert alert-info alert-dismissible fade show" role="alert">
            <i class="fa fa-info fa-4x fa-pull-left fa-border" aria-hidden="true"></i>
            <?= _('This page helps you building your phone Packages.')?><br>
            <br>
            <?= _('Create or modify your config files and replace the needed values with the Placeholders.')?><br />
            <?= _('With this information you can add new Phones to OSS EPM within minutes.')?><br />
            <br />
            <?= _('If you need a specific value to add your Phone you can make a feature request.')?>
            <button type="button" class="close" data-dismiss="alert" aria-label="Close">
                <span aria-hidden="true">&times;</span>
            </button>
        </div>

        <div id="toolbar-all-hwgrid">
        </div>
        <table 
            id="hwgrid"
            data-cache="false"
            data-toggle="table"
            data-pagination="true"
            data-show-columns="true"
            data-show-toggle="true"
            data-search="true"
            data-toolbar="#toolbar-all-hwgrid"
            class="table table-striped">
            <thead>
                <tr>
                    <th data-field="type" data-sortable="true"><?= _("Type") ?></th>
                    <th data-field="brand" data-sortable="true"><?= _("Brand") ?></th>
                    <th data-field="placeholder" data-sortable="true" data-formatter="copyButtonFormatter"><?= _("Placeholder") ?></th>
                    <th data-field="description" data-sortable="true"><?= _("Description") ?></th>
                </tr>
            </thead>
            <tbody>
            </tbody>
        </table>
	</div>
</div>