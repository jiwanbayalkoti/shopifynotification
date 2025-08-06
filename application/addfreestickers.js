let mainProductVariantId = 36110175633573;
let mainProductQuantity = 2;

// Fetch free sticker product data first
fetch('https://gyrovi-test.myshopify.com/products/free-sticker.js')
  .then(response => response.json())
  .then(product => {
    // Get the variant ID of the free sticker
    let freeStickerVariantId = product.variants[0].id;

    // Prepare cart add payload
    let formData = {
      items: [
        {
          id: mainProductVariantId,
          quantity: mainProductQuantity
        },
        {
          id: freeStickerVariantId,
          quantity: 1
        }
      ]
    };

    // Add both products to cart
    return fetch(window.Shopify.routes.root + 'cart/add.js', {
      method: 'POST',
      headers: {
        'Content-Type': 'application/json'
      },
      body: JSON.stringify(formData)
    });
  })
  .then(response => response.json())
  .then(data => {
    console.log('Products added to cart:', data);
    // Optional: redirect to cart page
    window.location.href = '/cart';
  })
  .catch((error) => {
    console.error('Error:', error);
  });
