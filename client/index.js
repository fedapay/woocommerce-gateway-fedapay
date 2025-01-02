/**
 * External dependencies
 */
import { registerPaymentMethod } from '@woocommerce/blocks-registry';
import { __ } from '@wordpress/i18n';
import { getSetting } from '@woocommerce/settings';
import { decodeEntities } from '@wordpress/html-entities';

/**
 * Internal dependencies
 */
import { PAYMENT_METHOD_NAME } from './constants';

const settings = getSetting( 'woo_gateway_fedapay_data', {} );
const defaultLabel = __( 'Fedapay', 'woo-gateway-fedapay' );
const label = decodeEntities( settings.title ) || defaultLabel;

/**
 * @typedef {import('@woocommerce/type-defs/registered-payment-method-props').RegisteredPaymentMethodProps} RegisteredPaymentMethodProps
 */

/**
 * Content component
 */
const Content = () => {
    return <div>{ decodeEntities( settings.description || '' ) }</div>;
};

/**
 * Label component
 *
 * @param {*} props Props from payment API.
 */
const Label = ( props ) => {
    return <div style={{ display: 'flex', flexDirection: 'row', rowGap: '.5em', alignItems: 'center'}}>
                <img
                    src={ settings.logo_url }
                    alt={ label }
                    style={{ height: "48px", maxHeight: "none" }}
                />
            </div>;
};

const canMakePayment = () => {
    return true
};

/**
 * Cash on Delivery (COD) payment method config object.
 */
const paymentMethod = {
    name: PAYMENT_METHOD_NAME,
    label: <Label />,
    content: <Content />,
    edit: <Content />,
    canMakePayment,
    ariaLabel: label,
    supports: {
        features: settings?.supports ?? [],
    },
};

registerPaymentMethod( paymentMethod );
