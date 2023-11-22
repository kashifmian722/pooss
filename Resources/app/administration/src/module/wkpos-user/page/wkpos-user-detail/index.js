

import template from './wkpos-user-detail.html.twig';

const { Component, Mixin } = Shopware;
const { Criteria } = Shopware.Data;
const {mapPropertyErrors} = Component.getComponentHelper();

Component.register('wkpos-user-detail', {
    template,

    inject: [
        'repositoryFactory',
        'WkposUserValidationApiService'
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
            user: null,
            imageSize: 140,
            isLoading: false,
            processSuccess: false,
            isUserLoading: false,
            repository: null,
            avatarMediaItem: null,
            oldPassword: null,
            newPassword: null,
            newPasswordConfirm: null,
            isEmailUsed: false,
            isUsernameUsed: false,
            uploadTag: 'sw-profile-upload-tag',
            changePasswordModal: false,
            userEditMode: true,
            languageId: null,
            isSaveSuccessful: false,
            outlets: []
        };
    },

    computed: {
        options() {
            return [{
                    value: 1,
                    name: this.$t('wkpos-user.detail.activeText')
                },
                {
                    value: 0,
                    name: this.$t('wkpos-user.detail.inActiveText')
                }
            ];
        },
        ...mapPropertyErrors('user', [
            'username',
            'firstName',
            'lastName',
            'email',
            'outletId',
            'password'
        ]),
        userRepository() {
            return this.repositoryFactory.create('wkpos_user');
        },
        outletRepository() {
            return this.repositoryFactory.create('wkpos_outlet');
        },
        mediaRepository() {
            return this.repositoryFactory.create('media');
        },
        disableConfirm() {
            return this.newPassword === '' || this.newPassword === null;
        },
        isError() {
            return this.isEmailUsed || this.isUsernameUsed;
        },
    },

    userMediaCriteria() {
        if (this.user.id) {
            // ToDo: If SwSidebarMedia has the new data handling, change this too
            return CriteriaFactory.equals('userId', this.user.id);
        }

        return null;
    },

    languageId() {
        return this.$store.state.adminLocale.languageId;
    },

    watch: {
        'user.avatarMedia'() {
            if (this.user.avatarMedia.id) {
                this.setMediaItem({
                    targetId: this.user.avatarMedia.id
                });
            }
        }
    },

    created() {
        this.repository = this.repositoryFactory.create('wkpos_user');
        this.getOutlets();
        this.getUser();
    },

    methods: {
        createdComponent() {
            this.isUserLoading = true;

            const languagePromise = new Promise((resolve) => {
                resolve(this.languageId);
            });

            if (this.$route.params.user) {
                this.userPromise = this.setUserData(this.$route.params.user);
            } else {
                this.userPromise = this.userService.getUser().then((response) => {
                    return this.setUserData(response.data);
                });
            }
            const promises = [
                languagePromise,
                this.userPromise
            ];

            Promise.all(promises).then(() => {
                this.loadLanguages();
            }).then(() => {
                this.isUserLoading = false;
            });
        },
        getUser() {
            this.repository
                .get(this.$route.params.id, Shopware.Context.api)
                .then((entity) => {
                    this.user = entity;
                    if (this.user.id) {
                        this.disable = true;
                    }
                    if (typeof this.setMediaItem != 'undefined') {
                        this.setMediaItem({
                            targetId: entity.avatarId
                        });
                    }
                    this.isUserLoading = false;
                    this.languageId = false;
                });
        },

        getOutlets() {
            const outletCriteria = new Criteria(1, 100);
            outletCriteria.addSorting(Criteria.sort('name'));
            this.outletRepository.search(outletCriteria, Shopware.Context.api).then((searchResult) => {
                this.outlets = searchResult;
            });
        },

        onClickSave() {
            this.isLoading = true;
            this.repository
                .save(this.user, Shopware.Context.api)
                .then(() => {
                    this.getUser();
                    this.isLoading = false;
                    this.processSuccess = true;
                    this.createNotificationSuccess({
                        title: this.$t('wkpos-user.detail.textSuccessTitle'),
                        message: this.$t('wkpos-user.detail.textSuccessMessage')
                    });
                }).catch((exception) => {
                    this.isLoading = false;
                    this.createNotificationError({
                        title: this.$t('wkpos-user.detail.errorTitle'),
                        message: exception
                    });
                });
        },

        checkPassword() {
            if (this.newPassword && this.newPassword.length > 0) {
                return this.validateOldPassword().then((oldPasswordIsValid) => {
                    if (oldPasswordIsValid === false) {
                        this.createErrorMessage(this.$tc('sw-profile.index.notificationOldPasswordErrorMessage'));
                        return false;
                    }

                    if (this.oldPassword === this.newPassword) {
                        this.createErrorMessage(this.$tc('sw-profile.index.notificationNewPasswordIsSameAsOldErrorMessage'));
                        return false;
                    }

                    if (this.newPassword !== this.newPasswordConfirm) {
                        this.createErrorMessage(this.$tc('sw-profile.index.notificationPasswordErrorMessage'));
                        return false;
                    }

                    this.user.password = this.newPassword;

                    return true;
                });
            }

            return null;
        },

        validateOldPassword() {
            return this.loginService.loginByUsername(this.user.username, this.oldPassword).then((response) => {
                return types.isString(response.access);
            }).catch(() => {
                return false;
            });
        },

        createErrorMessage(errorMessage) {
            this.createNotificationError({
                title: this.$tc('sw-profile.index.notificationPasswordErrorTitle'),
                message: errorMessage
            });
        },

        saveFinish() {
            this.processSuccess = false;
        },

        onSave() {
            if (this.checkEmail() === false) {
                return;
            }
            this.saveUser();
        },

        onCancel() {
            this.$router.push({
                name: 'sw.settings.user.list'
            });
        },

        onChangePassword() {
            this.changePasswordModal = true;
        },

        onClosePasswordModal() {
            this.newPassword = '';
            this.changePasswordModal = false;
        },

        onSubmit() {
            this.changePasswordModal = false;
            this.user.password = this.newPassword;
            this.newPassword = '';
            this.onSave();
        },

        checkEmail() {
            return this.WkposUserValidationApiService.checkUserEmail({
                email: this.user.email,
                id: this.user.id
            }).then(({
                emailIsUnique
            }) => {
                this.isEmailUsed = !emailIsUnique;
                if (this.isEmailUsed) {
                    this.createNotificationError({
                        title: this.$t('wkpos-user.detail.errorTitle'),
                        message: this.$t('wkpos-user.detail.errorEmailUsedMessage', 0, {
                            email: this.user.email
                        })
                    });
                }
            });
        },

        checkUsername() {
            return this.WkposUserValidationApiService.checkUserUsername({
                username: this.user.username,
                id: this.user.id
            }).then(({
                usernameIsUnique
            }) => {
                this.isUsernameUsed = !usernameIsUnique;
                if (this.isUsernameUsed) {
                    this.createNotificationError({
                        title: this.$t('wkpos-user.detail.errorTitle'),
                        message: this.$t('wkpos-user.detail.errorUsernameUsedMessage', 0, {
                            username: this.user.username
                        })
                    });
                }
            });
        },

        saveUser() {
            this.userRepository.save(this.user, Shopware.Context.api).then(() => {
                this.$refs.mediaSidebarItem.getList();
                this.oldPassword = '';
                this.newPassword = '';
                this.newPasswordConfirm = '';
                this.isLoading = false;
                this.isSaveSuccessful = true;
            }).catch(() => {
                this.isLoading = false;
            });
        },

        setMediaItem({
            targetId
        }) {
            this.mediaRepository.get(targetId, Shopware.Context.api).then((response) => {
                console.log(response);
                this.avatarMediaItem = response;
            });
            this.user.avatarId = targetId;
        },

        onDropMedia(mediaItem) {
            console.log(mediaItem);
            this.setMediaItem({
                targetId: mediaItem.id
            });
        },

        setMediaFromSidebar(mediaEntity) {
            this.avatarMediaItem = mediaEntity;
            this.user.avatarId = mediaEntity.id;
        },

        onUnlinkAvatar() {
            this.avatarMediaItem = null;
            this.user.avatarId = null;
        },

        openMediaSidebar() {
            this.$refs.mediaSidebarItem.openContent();
        }
    }
});
