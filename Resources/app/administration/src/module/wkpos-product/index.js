const { Module } = Shopware;
import './page/wkpos-product-list';
import './page/wkpos-barcode-print';
import deDE from './snippet/de-DE.json';
import enGB from './snippet/en-GB.json';

Module.register('wkpos-product', {
    type: 'core',
    name: 'product',
    title: 'wkpos-product.general.mainMenuItemGeneral',
    description: 'wkpos-product.general.descriptionTextModule',
    version: '1.0.0',
    targetVersion: '1.0.0',
    color: '#57D9A3',
    icon: 'default-symbol-products',
    favicon: 'icon-module-products.png',
    entity: 'product',

    snippets: {
        'de-DE': deDE,
        'en-GB': enGB
    },

    routes: {
        index: {
            components: {
                default: 'wkpos-product-list'
            },
            path: 'index',
            meta: {
                parentPath: 'sw.settings.index'
            }
        },
        print: {
            components: {
                default: 'wkpos-barcode-print'
            },
            path:  'print/:id/:barcode',
            meta: {
                parentPath: 'sw.settings.index'
            }
        }
    },

    settingsItem: [{
        name: 'wkpos-product',
        label: 'wkpos-product.general.mainMenuItemGeneral',
        icon: 'default-object-scale',
        to: 'wkpos.product.index',
        group: 'plugins'
    }]
});
