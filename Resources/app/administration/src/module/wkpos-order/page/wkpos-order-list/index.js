
import template from './wkpos-order-list.html.twig';

const { Component } = Shopware;
const { Criteria } = Shopware.Data;

Component.register('wkpos-order-list', {
    template,

    inject: [
        'repositoryFactory',
        'stateStyleDataProviderService'
    ],

    data() {
        return {
            repository: null,
            orders: [],
            sortBy: 'createdAt',
            sortDirection: 'DESC',
            isLoading: false,
            limit: 25,
            page: 1,
            total: 0
        };
    },

    metaInfo() {
        return {
            title: this.$createTitle()
        };
    },

    methods: {
        getVariantFromOrderState(order) {
            return this.stateStyleDataProviderService.getStyle(
                'order.state', order.stateMachineState.technicalName
            ).variant;
        },
        getVariantFromPaymentState(order) {
            return this.stateStyleDataProviderService.getStyle(
                'order_transaction.state', order.transactions[0].stateMachineState.technicalName
            ).variant;
        },
        getList() {
            this.isLoading = true;
            this.repository = this.repositoryFactory.create('wkpos_order');
            const criteria = new Criteria(this.page, this.limit);
            criteria.addAssociation('order');
            criteria.addAssociation('user');
            criteria.addAssociation('order.currency');
            criteria.addAssociation('order.transactions');
            criteria.addAssociation('user.outlet');
            criteria.addSorting(Criteria.sort('createdAt', 'DESC'));
    
            this.repository
                .search(criteria, Shopware.Context.api)
                .then((result) => {
                    this.isLoading = false;
                    this.total = result.total;
                    this.orders = result;
                });
        },
        onPageChange({ page = 1, limit = 25 }) {
            this.page = page;
            this.limit = limit;
            this.getList();
        }
    },
    computed: {
        columns() {
            return [{
                property: 'order.orderNumber',
                label: this.$t('wkpos-order.list.columnOrderNumber'),
                routerLink: 'sw.order.detail',
                inlineEdit: false,
                allowResize: true,
                primary: true
            }, {
                property: 'userName',
                label: this.$t('wkpos-order.list.columnUser'),
                allowResize: true
            }, {
                property: 'user.outlet.name',
                label: this.$t('wkpos-order.list.columnOutlet'),
                allowResize: true
            }, {
                property: 'order.orderCustomer.firstName',
                label: this.$t('wkpos-order.list.columnCustomer'),
                inlineEdit: false,
                allowResize: true
            }, {
                property: 'order.amountTotal',
                label: this.$t('wkpos-order.list.columnTotal'),
                allowResize: true
            }, {
                property: 'order.stateMachineState.name',
                label: this.$t('wkpos-order.list.columnOrderStatus'),
                allowResize: true
            }];
        }
    },
    created() {
        this.getList();
    },

   
});
