// @ts-check

/**
 * @typedef {{
 *  submitButton: HTMLElement|HTMLButtonElement|null,
 *  form: ?HTMLElement,
 *  locale: string
 * }} ApplePaySettings
 */

/**
 * @typedef {{
 *  NOT_SUPPORTED: string
 *  CANCEL_BY_USER: string
 * }} ApplePaySnippets
 */

/**
 * @typedef {{
 *  $errorHolder: ?jQuery<HTMLElement>,
 *  $form: ?jQuery<HTMLElement>,
 *  submitButton: HTMLElement|HTMLButtonElement|null,
 *  locale: ?string,
 *  customerId: ?string,
 *  customer: ?object,
 *  autoSubmit: ?boolean,
 *  amount: null|string|number,
 *  currency: ?string,
 *  effectiveInterest: null|string|number,
 *  orderDate: ?string,
 *  styling: {fontSize: string, fontFamily: string, fontColor: string},
 *  isB2B: ?boolean,
 *  county: ?string
 * }} PaymentSettings
 */

/**
 * @typedef {{
 *  countryCode: string,
 *  currencyCode: string,
 *  supportedNetworks: string[],
 *  merchantCapabilities: string[],
 *  total: { label: string, amount: number },
 *  lineItems: Array<{label: string, amount: number, type: string}>
 * }} ApplePayPaymentRequest
 */