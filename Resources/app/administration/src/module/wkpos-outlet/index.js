const { Module } = Shopware;
import './page/wkpos-outlet-list';
import './page/wkpos-outlet-detail';
import './page/wkpos-outlet-create';
import deDE from './snippet/de-DE';
import enGB from './snippet/en-GB';

Module.register('wkpos-outlet', {
    type: 'plugin',
    name: 'POS Outlet',
    title: 'wkpos-outlet.general.mainMenuItemGeneral',
    description: 'wkpos-outlet.general.descriptionTextModule',
    color: '#997b2d',
    icon: 'default-building-home',

    snippets: {
        'de-DE': deDE,
        'en-GB': enGB
    },

    routes: {
        list: {
            component: 'wkpos-outlet-list',
            path: 'list',
            meta: {
                parentPath: 'sw.settings.index'
            }
        },
        detail: {
            component: 'wkpos-outlet-detail',
            path: 'detail/:id',
            meta: {
                parentPath: 'wkpos.outlet.list'
            }
        },
        create: {
            component: 'wkpos-outlet-create',
            path: 'create',
            meta: {
                parentPath: 'wkpos.outlet.list'
            }
        }
    },

    settingsItem: [ {
        name: "wkpos-outlet",
        label: 'wkpos-outlet.general.mainMenuItemGeneral',
        to: 'wkpos.outlet.list',
        icon: 'default-object-scale',
        group: 'plugins'
    }]
});
