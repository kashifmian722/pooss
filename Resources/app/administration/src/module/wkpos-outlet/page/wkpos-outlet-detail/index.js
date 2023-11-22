import template from './wkpos-outlet-detail.html.twig';
import './wkpos-outlet-detail.scss';

const { Component, Mixin } = Shopware;
const { Criteria } = Shopware.Data;
const { mapPropertyErrors } = Shopware.Component.getComponentHelper();
Component.register('wkpos-outlet-detail', {
    template,

    inject: [
        'repositoryFactory',
        'WkposApiService'
    ],

    mixins: [
        Mixin.getByName('notification')
    ],

    metaInfo() {
        return {
            title: this.$createTitle()
        };
    },

    data() {
        return {
            outlet: null,
            isLoading: false,
            processSuccess: false,
            repository: null,
            outletEditMode: true,
            countries: []
        };
    },

    computed: {
        options() {
            return [{
                    value: 1,
                    name: this.$t('wkpos-outlet.detail.activeText')
                },
                {
                    value: 0,
                    name: this.$t('wkpos-outlet.detail.inActiveText')
                }
            ];
        },
        ...mapPropertyErrors('outlet',[
            'name',
            'address',
            'city',
            'countryId',
            'zipcode'
        ]),

        countryRepository() {
            return this.repositoryFactory.create('country');
        }
    },

    created() {
        this.repository = this.repositoryFactory.create('wkpos_outlet');
        this.getOutlet();
        this.getCountries();
    },

    methods: {
        getOutlet() {
            this.repository
                .get(this.$route.params.id, Shopware.Context.api)
                .then((entity) => {
                    this.outlet = entity;
                });
        },

        getCountries() {
            const countryCriteria = new Criteria(1);
            countryCriteria.addSorting(Criteria.sort('name'));
            this.countryRepository.search(countryCriteria, Shopware.Context.api).then((searchResult) => {
                this.countries = searchResult;
            });
        },

        onClickSave() {
            this.isLoading = true;
            this.repository
                .save(this.outlet, Shopware.Context.api)
                .then(() => {
                    this.getOutlet();
                    this.isLoading = false;
                    this.processSuccess = true;
                    this.createNotificationSuccess({
                        title: this.$t('wkpos-outlet.detail.textSuccessTitle'),
                        message: this.$t('wkpos-outlet.detail.textSuccessMessage')
                    });
                }).catch((exception) => {
                    this.isLoading = false;
                    this.createNotificationError({
                        title: this.$t('wkpos-outlet.detail.errorTitle'),
                        message: this.$t('wkpos-outlet.detail.errorMessage')
                    });
                });
        },

        saveFinish() {
            this.processSuccess = false;
        },
        async getSelected(item) {
            var status = 0
            await this.posProducts.forEach(product => {
                if (item.item.id == product.productId) {
                    if (product.active) {
                        status = 1;
                    }
                }
            })
            return status;
        }
    },
    watch: {
        $route(to, from) {
            if (to.name == 'wkpos.outlet.detail.product') {
                this.getList();
                this.showOutlet = false;
                this.showProduct = true;
            } else {
                this.showOutlet = true;
                this.showProduct = false;
            }
        },
        $refs: function (ref) {
            console.log(ref);

        }

    },
    mounted: function () {

    }
});
