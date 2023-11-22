const { Component } = Shopware;
const {mapPropertyErrors} = Component.getComponentHelper();
import template from './wkpos-user-create.html.twig';

Component.extend('wkpos-user-create', 'wkpos-user-detail', {

    template,

    methods: {
        getUser() {
            this.user = this.repository.create(Shopware.Context.api);
        },

        onClickSave() {
            this.isLoading = true;
            this.repository
                .save(this.user, Shopware.Context.api)
                .then(() => {
                    this.isLoading = false;
                    this.createNotificationSuccess({
                        title: this.$t('wkpos-user.detail.textSuccessTitle'),
                        message: this.$t('wkpos-user.detail.textSuccessMessage')
                    });
                    this.$router.push({
                        name: 'wkpos.user.detail',
                        params: {
                            id: this.user.id
                        }
                    });
                }).catch((exception) => {
                    this.isLoading = false;
                    this.createNotificationError({
                        title: this.$t('wkpos-user.detail.errorTitle'),
                        message: this.$t('wkpos-user.detail.errorSaveMessage')
                    });
                });
        }
    },
    computed: {
        ...mapPropertyErrors('user', [
            'username',
            'firstName',
            'lastName',
            'email',
            'outletId',
            'password'
        ]),
    }
});
