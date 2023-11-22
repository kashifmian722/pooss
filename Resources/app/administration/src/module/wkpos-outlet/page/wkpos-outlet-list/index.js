
import template from './wkpos-outlet-list.html.twig';
import './wkpos-outlet-list.scss';

const { Component } = Shopware;
const { Criteria } = Shopware.Data;

Component.register('wkpos-outlet-list', {
    template,

    inject: [
        'repositoryFactory'
    ],

    data() {
        return {
            repository: null,
            outlets: null,
            isLoading: false,
            total: 0
        };
    },

    metaInfo() {
        return {
            title: this.$createTitle()
        };
    },

    computed: {
        columns() {
            return [{
                property: 'name',
                dataIndex: 'name',
                label: this.$t('wkpos-outlet.list.columnName'),
                routerLink: 'wkpos.outlet.detail',
                inlineEdit: 'string',
                allowResize: true,
                primary: true
            }, {
                property: 'address',
                dataIndex: 'address',
                label: this.$t('wkpos-outlet.list.columnAddress'),
                allowResize: true
            }, {
                property: 'active',
                dataIndex: 'active',
                label: this.$t('wkpos-outlet.list.columnActive'),
                inlineEdit: 'boolean',
                allowResize: true
            }];
        }
    },

    created() {
        this.repository = this.repositoryFactory.create('wkpos_outlet');
        this.isLoading = true;
        const criteria = new Criteria();
        criteria.addAssociation('country');
        this.repository
            .search(criteria, Shopware.Context.api)
            .then((result) => {
                this.isLoading = false;
                this.outlets = result;
                this.total = result.total;
            });
    }
});
