MODx.form.SupportWidget = function (config) {
    config = config || {};
    Ext.applyIf(config, {
        url: config.connector_url,
        baseParams: {
            action: 'mgr/form/submit'
        },
        layout: 'form',
        defaults: {
            xtype: 'textfield',
            anchor: '100%'
        },
        padding: '8px',
        items: [
            {
                xtype: 'textfield',
                fieldLabel: _('modxsupport.widget.fullname'),
                name: 'fullname',
                value: config.userDetails.fullname,
                hideLabel: false
            },
            {
                xtype: 'textfield',
                fieldLabel: _('modxsupport.widget.email'),
                name: 'email',
                value: config.userDetails.email,
                hideLabel: false
            },
            {
                xtype: 'textarea',
                fieldLabel: _('modxsupport.widget.message'),
                name: 'message',
                hideLabel: false
            }
        ],

        buttons: [{
            text: 'Send',
            type: 'submit',
            process: 'mgr/form/submit'
        }],
        useLoadingMask: true
    });
    MODx.form.SupportWidget.superclass.constructor.call(this, config);
};
Ext.extend(MODx.form.SupportWidget, MODx.FormPanel, {

});
Ext.reg('modx-form-supportwidget', MODx.form.SupportWidget);