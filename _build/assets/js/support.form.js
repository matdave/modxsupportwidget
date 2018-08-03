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
                name: 'fullname',
                value: config.userDetails.fullname
            },
            {
                xtype: 'textfield',
                name: 'email',
                value: config.userDetails.email
            },
            {
                xtype: 'textarea',
                name: 'message'
            }
        ]
    });
    MODx.FormSupportWidget.superclass.constructor.call(this, config);
};
Ext.extend(MODx.FormSupportWidget, MODx.FormPanel, {

});
Ext.reg('modx-form-supportwidget', MODx.FormSupportWidget);