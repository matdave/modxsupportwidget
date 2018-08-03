MODx.form.SupportWidget = function (config) {
    config = config || {};
    Ext.applyIf(config, {
        url: config.connector_url,
        baseParams: {
            action: 'mgr/form/submit'
        },
        labelWidth: 55,
        layout: {
            type: 'vbox',
            align: 'stretch'  // Child items are stretched to full width
        },
        defaults: {
            xtype: 'textfield'
        },
        items: [
            {
                xtype: 'textfield',
                fieldLabel: 'Fullname',
                name: 'fullname',
                value: config.userDetails.fullname,
                hideLabel: false
            },
            {
                xtype: 'textfield',
                fieldLabel: 'Email',
                name: 'email',
                value: config.userDetails.email,
                hideLabel: false
            },
            {
                xtype: 'textarea',
                fieldLabel: 'Message',
                name: 'message',
                hideLabel: false
            }
        ],

        buttons: [{
            text: 'Send'
        }]
    });
    MODx.form.SupportWidget.superclass.constructor.call(this, config);
};
Ext.extend(MODx.form.SupportWidget, MODx.FormPanel, {

});
Ext.reg('modx-form-supportwidget', MODx.form.SupportWidget);