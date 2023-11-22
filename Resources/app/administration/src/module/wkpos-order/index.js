const { Module } = Shopware;
import './page/wkpos-order-list';

import deDE from './snippet/de-DE';
import enGB from './snippet/en-GB';

Module.register('wkpos-order', {
    type: 'plugin',
    name: 'POS Order',
    title: 'wkpos-order.general.mainMenuItemGeneral',
    description: 'wkpos-order.general.descriptionTextModule',
    color: '#A092F0',
    icon: 'default-shopping-paper-bag',
    favicon: 'icon-module-orders.png',

    snippets: {
        'de-DE': deDE,
        'en-GB': enGB
    },

    routes: {
        list: {
            component: 'wkpos-order-list',
            path: 'list',
            meta: {
                parentPath:  'sw.settings.index'
            }
        },
        detail: {
            component: 'sw-order-detail',
            path: 'detail/:orderId',
            meta: {
                parentPath: 'wkpos.order.list'
            },
        }
    },

    settingsItem: [{
        name: "wkpos-order",
        label: 'wkpos-order.general.mainMenuItemGeneral',
        to: 'wkpos.order.list',
        icon: 'default-object-scale',
        group: 'plugins'
    }]
});
