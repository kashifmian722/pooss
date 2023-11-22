import template from './wkpos-product-list.html.twig';
import './wkpos-product-list.scss';
import './jsBarcode.all';
import image from './img/barcode.svg'

const { Component, Mixin } = Shopware;
const { Criteria } = Shopware.Data;

Component.register('wkpos-product-list', {
    template,

    inject: [
        'repositoryFactory',
        'WkposApiService'
    ],

    mixins: [
        Mixin.getByName('notification'),
        Mixin.getByName('listing'),
        Mixin.getByName('placeholder')
    ],

    data() {
        return {
            repository: null,
            isLoading: false,
            sortBy: 'productNumber',
            sortDirection: 'DESC',
            isBulkLoading: false,
            processSuccess: false,
            outletId: null,
            productIds: [],
            products: null,
            outlets: [],
            currencies: {},
            wkposProducts: null,
            selectedItems: {},
            quantity: {},
            total: 0,
            barcodeIdSuffix: 'pos',
            defaultOutletId: null,
            imgurlAlt: image,
        };
    },

    metaInfo() {
        return {
            title: this.$createTitle()
        };
    },

    computed: {
        productRepository() {
            return this.repositoryFactory.create('product');
        },

        productColumns() {
            return this.getProductColumns();
        },
        currencyRepository() {
            return this.repositoryFactory.create('currency');
        },
        outletRepository() {
            return this.repositoryFactory.create('wkpos_outlet');
        },
        posProductRepository() {
            return this.repositoryFactory.create('wkpos_product');
        }
        
    },

    filters: {
        stockColorVariant(value) {
            if (value >= 25) {
                return 'success';
            }
            if (value < 25 && value > 0) {
                return 'warning';
            }

            return 'error';
        }
    },

    methods: {
        async getList() {
            this.isLoading = true;

            const productCriteria = new Criteria(this.page, 100);
            this.naturalSorting = this.sortBy === 'productNumber';
            
            productCriteria.setTerm(this.term);
            productCriteria.addFilter(Criteria.equals('product.parentId', null));
            productCriteria.addSorting(Criteria.sort(this.sortBy, this.sortDirection, this.naturalSorting));
            productCriteria.addAssociation('cover');
            productCriteria.addAssociation('wkpos_barcode');

            const currencyCriteria = new Criteria(1, 100);

            try {
                const result = await Promise.all([
                    this.productRepository.search(productCriteria, Shopware.Context.api),
                    this.currencyRepository.search(currencyCriteria, Shopware.Context.api)
                ]);
                const products = result[0];
                const currencies = result[1];
                this.total = products.total;
                this.products = products;
                console.log(this.products)
                this.productMedia = this.products.map(function (product) {
                    return product.cover.media;
                });

                this.currencies = currencies;
                this.isLoading = false;
                this.selection = {};
                this.showBarcode();
            } catch (e) {
                this.isLoading = false;
            }
        },
        getProductColumns() {
            return [{
                property: 'name',
                label: this.$tc('sw-product.list.columnName'),
                sortable: false,
                routerLink: 'sw.product.detail',
                allowResize: true,
                primary: true
            }, {
                property: 'productNumber',
                naturalSorting: true,
                label: this.$tc('sw-product.list.columnProductNumber'),
                sortable: false,
                align: 'right',
                allowResize: true
            }, {
                property: 'active',
                label: this.$tc('sw-product.list.columnActive'),
                sortable: false,
                allowResize: true,
                align: 'center'
            }, {
                property: 'stock',
                label: this.$tc('sw-product.list.columnInStock'),
                sortable: false,
                allowResize: true,
                align: 'right'
            }, {
                property: 'availableStock',
                label: this.$tc('sw-product.list.columnAvailableStock'),
                sortable: false,
                allowResize: true,
                align: 'right'
            }, {
                property: 'assing',
                sortable: false,
                dataIndex: 'availableStock',
                label: this.$tc('wkpos-product.list.columnAssignedStock'),
                allowResize: false,
                align: 'center'
            }, {
                property: 'action',
                sortable: false,
                label: this.$tc('wkpos-product.list.columnAction'),
                allowResize: true,
                align: 'right'
            },
            {
                property: 'barcode',
                sortable: false,
                label: this.$tc('wkpos-product.list.columnBarcode'),
                allowResize: true,
                align: 'right'
            }
        ];
        },
        getOutlets() {
            const outletCriteria = new Criteria(1, 100);
            outletCriteria.addSorting(Criteria.sort('name'));
            this.outletRepository.search(outletCriteria, Shopware.Context.api).then((searchResult) => {
                this.outlets = searchResult;
            });
        },
        getPosProducts() {
            this.posProductRepository.search(
                new Criteria(1, 100), Shopware.Context.api
            ).then(result => {
                this.wkposProducts = result;
                this.showBarcode();
            });
        },
        getPosStock(item) {
            var posProduct = this.wkposProducts.map(function (product) {
                return product.productId == item.item.id ? product : false;
            });

            if (posProduct) {
                return posProduct.stock;
            } else {
                return '';
            }
        },
        getCurrencyPriceByCurrencyId(itemId, currencyId) {
            let foundPrice = {
                currencyId: null,
                gross: null,
                linked: true,
                net: null
            };

            // check if products are loaded
            if (!this.products) {
                return foundPrice;
            }

            // find product for itemId
            const foundProduct = this.products.find((item) => {
                return item.id === itemId;
            });

            // find price from product with currency id
            if (foundProduct) {
                const priceForProduct = foundProduct.price.find((price) => {
                    return price.currencyId === currencyId;
                });

                if (priceForProduct) {
                    foundPrice = priceForProduct;
                }
            }
            return foundPrice;
        },
        async assignToOutlet(product) {
           
            var productId = [product.id];
            var stock = this.quantity[product.id] ? this.quantity[product.id] : 1;
            if (!this.outletId) {
                return this.createNotificationError({
                    title: this.$tc('wkpos-product.list.titleSaveError'),
                    message: this.$tc('wkpos-product.list.messageSelectOutlet')
                });
            }

            // if (product.availableStock < stock) {
            //     return this.createNotificationError({
            //         title: this.$tc('wkpos-product.list.titleSaveError'),
            //         message: this.$tc('wkpos-product.list.messageLowStock', 0, {
            //             name: product.translated.name
            //         })
            //     });
            // }

            try {
                const result = await Promise.all([
                    this.WkposApiService.assignProducts(
                        this.outletId, productId, stock
                    )
                ]).then(response => {
                    this.createNotificationSuccess({
                        title: this.$tc('wkpos-product.list.titleSaveSuccess'),
                        message: this.$tc('wkpos-product.list.messageSaveSuccess', 0, {
                            name: product.translated.name
                        })
                    });
                });
            } catch (e) {
                this.isLoading = false;
            }
        },
        onSelectionChanged(selection) {
            this.selectedItems = selection;
        },

        async assignProducts() {
            var productIds = Object.keys(this.selectedItems);

            if (!this.outletId) {
                return this.createNotificationError({
                    title: this.$tc('wkpos-product.list.titleSaveError'),
                    message: this.$tc('wkpos-product.list.messageSelectOutlet'),
                });
            }

            if (productIds.length < 1) {
                return this.createNotificationError({
                    title: this.$tc('wkpos-product.list.titleSaveError'),
                    message: this.$tc('wkpos-product.list.messageSelectProduct'),
                });
            }
            
            try {
                const result = await Promise.all([
                    this.WkposApiService.assignProducts(this.outletId, productIds, this.quantity)
                ]).then(result => {
                    this.createNotificationSuccess({
                        title: this.$tc('wkpos-product.list.titleSaveSuccess'),
                        message: this.$tc('wkpos-product.list.messageAssignSuccess', 0, {
                            count: result[0]['count']
                        })
                    });
                });
            } catch (e) {
                this.isLoading = false;
            }
        },
        generateBarcode(id) {
            let barcode = 'pos'+ id.substr(id.length - 4);
            JsBarcode('#pos'+id, barcode,{
                displayValue: false
              });
            this.WkposApiService.generateBarcode(id,barcode).then(result => {
                this.getList();
                this.showBarcode();
                this.createNotificationSuccess({
                    title: this.$tc('wkpos-product.list.titleSaveSuccess'),
                    message: this.$tc('wkpos-product.list.messageBarcodeGenerate')
                });
            })
        },
        showBarcode(){
            this.isLoading = true;
            let barcodeRepository = this.repositoryFactory.create('wkpos_barcode');
            barcodeRepository.search((new Criteria()),Shopware.Context.api).then(result => {
                result.forEach(element => {
                    if(element){

                        JsBarcode('#pos'+element.productId, element.barcode,{
                            displayValue: false
                          });
                    }
                });
            })
            this.isLoading = false;
        },
        
        onChangeOutlet(outletId) {
            this.outletId = outletId;
            this.showAssignQuantity(outletId);
        },
        async showAssignQuantity(outletId) {
            this.isLoading = true;
            
            const criteria = new Criteria();
            criteria.addFilter(Criteria.equals('outletId',outletId));
            const posProducts = await Promise.all([
              this.posProductRepository.search(criteria, Shopware.Context.api)  
            ])
            var productIds =[];
            posProducts[0].forEach(element => {
                this.quantity[element.productId] = element.stock;
                productIds.push(element.productId);
                
            });
           
            for (const [key, value] of Object.entries(this.quantity)) {
                if(productIds.indexOf(key) == -1) {
                    delete this.quantity[key];
                }
              }
            this.getList();
            this.showBarcode();
            this.isLoading = false;

        },
        
        
    },
    

    created() {
        this.getList();
        this.getOutlets();
        this.getPosProducts();
        this.showBarcode();
        
    },
    mounted() {
        this.showBarcode();
        this.showAssignQuantity(this.defaultOutletId);
    }
});
