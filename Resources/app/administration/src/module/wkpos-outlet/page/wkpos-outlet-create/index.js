const { Component } = Shopware;
const {mapPropertyErrors} = Component.getComponentHelper();

Component.extend('wkpos-outlet-create', 'wkpos-outlet-detail', {
    methods: {
        getOutlet() {
            this.outlet = this.repository.create(Shopware.Context.api);
        },

        onClickSave() {
            this.isLoading = true;
            this.repository
                .save(this.outlet, Shopware.Context.api)
                .then(() => {
                    this.isLoading = false;
                    this.createNotificationSuccess({
                        title: this.$t('wkpos-outlet.detail.textSuccessTitle'),
                        message: this.$t('wkpos-outlet.detail.textSuccessMessage')
                    });
                    this.$router.push({
                        name: 'wkpos.outlet.detail',
                        params: {
                            id: this.outlet.id
                        }
                    });
                }).catch((exception) => {
                    this.isLoading = false;

                    this.createNotificationError({
                        title: this.$t('wkpos-outlet.detail.errorTitle'),
                        message: this.$t('wkpos-outlet.detail.errorMessage')
                    });
                });
        },
        computed: {
            ...mapPropertyErrors('outlet',[
                'name',
                'address',
                'city',
                'countryId',
                'zipcode'
            ]),
        }
    }
});
