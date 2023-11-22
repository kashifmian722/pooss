const { Module } = Shopware;
import './page/wkpos-user-list';
import './page/wkpos-user-detail';
import './page/wkpos-user-create';
import deDE from './snippet/de-DE';
import enGB from './snippet/en-GB';

Module.register('wkpos-user', {
    type: 'plugin',
    name: 'POS user',
    title: 'wkpos-user.general.mainMenuItemGeneral',
    description: 'wkpos-user.general.descriptionTextModule',
    color: '#997b2d',
    icon: 'default-avatar-single',
    favicon: 'icon-module-customers.png',

    snippets: {
        'de-DE': deDE,
        'en-GB': enGB
    },

    routes: {
        list: {
            component: 'wkpos-user-list',
            path: 'list',
            meta: {
                parentPath: 'sw.settings.index'
            }
        },
        detail: {
            component: 'wkpos-user-detail',
            path: 'detail/:id',
            meta: {
                parentPath: 'wkpos.user.list'
            }
        },
        create: {
            component: 'wkpos-user-create',
            path: 'create',
            meta: {
                parentPath: 'wkpos.user.list'
            }
        }
    },

    settingsItem: [{
        name: "wkpos-user",
        label: 'wkpos-user.general.mainMenuItemGeneral',
        to: 'wkpos.user.list',
        icon: 'default-object-scale',
        group: 'plugins'
    }]
});
