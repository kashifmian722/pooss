const { ApiService}  = Shopware.Classes;
class WkposApiService extends ApiService {
    constructor(httpClient, loginService, apiEndpoint = 'wkpos') {
        super(httpClient, loginService, apiEndpoint);
    }

    getProducts(outletId = 0) {
        const apiRoute = `${this.getApiBasePath()}/product/list`
        return this.httpClient.post(
            apiRoute, {}, {
                headers: this.getBasicHeaders()
            }
        ).then((response) => {
            return ApiService.handleResponse(response);
        });
    }

    assignProducts(outletId, productIds, stock) {
        const apiRoute = `${this.getApiBasePath()}/assign/products`;
        return this.httpClient.post(
            apiRoute, {
                outletId: outletId,
                productIds: productIds,
                stock: stock
            }, {
                headers: this.getBasicHeaders()
            }
        ).then((response) => {
            return ApiService.handleResponse(response);
        });
    }

    getBarcode(productId) {
        const apiRoute = `${this.getApiBasePath()}/product/barcode/${productId}`;
        return this.httpClient.post(
            apiRoute, {}, {
                headers: this.getBasicHeaders()
            }
        ).then((response) => {
            return ApiService.handleResponse(response);
        });
    }

    getBarcodes(productIds) {
        const apiRoute = `${this.getApiBasePath()}/product/barcodes`;

        return this.httpClient.post(
            apiRoute, {
                // productIds: productIds
            }, {
                headers: this.getBasicHeaders()
            }
        ).then((response) => {
            return ApiService.handleResponse(response);
        });
    }

    generateBarcode(productId,barcode) {
        const apiRoute = `${this.getApiBasePath()}/barcode/generate/`;
        return this.httpClient.post(
            apiRoute, {id:productId,code:barcode}, {
                headers: this.getBasicHeaders()
            }
        ).then((response) => {
            return ApiService.handleResponse(response);
        });
    }
    getProductAssignedStatus(outletId, productId) {
        const apiRoute = `${this.getApiBasePath()}/product/assigned-status`;
        return this.httpClient.post(
            apiRoute, {
                productId: productId,
                outletId: outletId
            }, {
                headers: this.getBasicHeaders()
            }
        ).then((response) => {
            return ApiService.handleResponse(response);
        });
    }

}

export default WkposApiService;
