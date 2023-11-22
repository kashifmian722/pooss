
import template from './barcode-print.html.twig';
import '../wkpos-product-list/jsBarcode.all'

const { Component } = Shopware;
const { Criteria } = Shopware.Data;

Component.register('wkpos-barcode-print',{
    template,
    data() {
        return {
            productId: this.$route.params.id,
            barcode: this.$route.params.barcode,
            barcodeIdSuffix: 'pos',
            loading: false

        }
    },
    methods:{
         printBarcode(){
             this.loading = true;
            JsBarcode('#pos', this.barcode);
        },
        printImage(){
            document.getElementsByClassName('sw-admin-menu is--expanded')[0].style.display = "none";
            document.getElementById('wkpos-print-button').style.display = "none";
            document.getElementById('wkpos-back-button').style.display = "none";
            window.print();
            document.getElementsByClassName('sw-admin-menu is--expanded')[0].style.display = "block";
            document.getElementById('wkpos-print-button').style.display = "block";
            document.getElementById('wkpos-back-button').style.display = "block";
        }
    },
    mounted:function(){
        this.printBarcode();
    }
})