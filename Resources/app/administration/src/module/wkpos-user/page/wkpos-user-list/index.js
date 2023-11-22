
import template from './wkpos-user-list.html.twig';

const { Component } = Shopware;
const { Criteria } = Shopware.Data;

Component.register('wkpos-user-list', {
    template,

    inject: [
        'repositoryFactory'
    ],

    data() {
        return {
            repository: null,
            users: null,
            total: 0,
            isLoading: false
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
                property: 'username',
                dataIndex: 'username',
                label: this.$t('wkpos-user.list.columnUsername'),
                routerLink: 'wkpos.user.detail',
                inlineEdit: 'string',
                allowResize: true,
                primary: true
            }, {
                property: 'firstName',
                dataIndex: 'firstName',
                inlineEdit: 'string',
                label: this.$t('wkpos-user.list.columnFirstName'),
                allowResize: true
            }, {
                property: 'lastName',
                dataIndex: 'lastName',
                inlineEdit: 'string',
                label: this.$t('wkpos-user.list.columnLastName'),
                allowResize: true
            }, {
                property: 'email',
                dataIndex: 'email',
                inlineEdit: 'string',
                label: this.$t('wkpos-user.list.columnEmail'),
                allowResize: true
            }, {
                property: 'active',
                dataIndex: 'active',
                inlineEdit: 'boolean',
                label: this.$t('wkpos-user.list.columnActive'),
                allowResize: true
            }];
        }
    },

    created() {
        this.isLoading = true;
        this.repository = this.repositoryFactory.create('wkpos_user');
        const criteria = new Criteria(this.page, this.limit);
        criteria.addAssociation('outlet');
        this.repository
            .search(criteria, Shopware.Context.api)
            .then((result) => {
                this.total = result.total;
                this.users = result;
                this.isLoading = false;
            });
    }
});
