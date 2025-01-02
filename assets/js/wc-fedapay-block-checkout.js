const settings = window.wc.wcSettings.getSetting( 'woo_gateway_fedapay_data', {} );
const label = window.wp.htmlEntities.decodeEntities( settings.title ) || window.wp.i18n.__( 'Fedapay', 'woo-gateway-fedapay' );
const Content = () => {
    return window.wp.htmlEntities.decodeEntities( settings.description || '' );
};

console.log(window.wp);

// This is the label for the payment method
const Label = ( props ) => {
    const { PaymentMethodLabel } = props.components;
    console.log(props);

    return null;
};
const Block_Gateway = {
    name: 'woo_gateway_fedapay',
    label: Label,
    content: Object( window.wp.element.createElement )( Content, null ),
    edit: Object( window.wp.element.createElement )( Content, null ),
    canMakePayment: () => true,
    ariaLabel: label,
    supports: {
        features: settings.features,
    },
};
window.wc.wcBlocksRegistry.registerPaymentMethod( Block_Gateway );
