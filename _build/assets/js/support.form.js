MODx.FormSupportWidget = function (config) {
    config = config || {};
    Ext.applyIf(config, {
        url: config.connector_url,
        baseParams: {
            action: 'mgr/form/submit'
        },
        items: [
            {
                xtype: 'textfield',
                fieldLabel: _('modxsupport.widget.fullname'),
                name: 'fullname',
                value: config.userDetails.fullname
            },
            {
                xtype: 'textfield',
                fieldLabel: _('modxsupport.widget.email'),
                name: 'email',
                value: config.userDetails.email
            },
            {
                xtype: 'textarea',
                fieldLabel: _('modxsupport.widget.message'),
                name: 'message'
            }
        ]
    });
    MODx.FormSupportWidget.superclass.constructor.call(this, config);
};
Ext.extend(MODx.FormSupportWidget, MODx.FormPanel, {

});
Ext.reg('modx-form-supportwidget', MODx.FormSupportWidget);