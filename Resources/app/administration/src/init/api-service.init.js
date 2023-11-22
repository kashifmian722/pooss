const { Application } = Shopware;
import WkposApiService from '../../src/core/service/api/wkpos-api.service';
import WkposUserValidationApiService from '../../src/core/service/api/user-validation-api.service';

Application.addServiceProvider('WkposApiService', (container) => {
    const initContainer = Application.getContainer('init');
    return new WkposApiService(initContainer.httpClient, container.loginService);
});

Application.addServiceProvider('WkposUserValidationApiService', (container) => {
    const initContainer = Application.getContainer('init');
    return new WkposUserValidationApiService(initContainer.httpClient, container.loginService);
});