### Plugin is still under development, but some endpoints have been implemented and tested.

YITH Woocommerce wish list REST API plugin exposes REST point to works with YITH Woocommerce wish list data.

**Namespace:** `/yith/wishlist/v2`

**Full URL:** `/wp-json/yith/wishlist/v2`

## Endpoints

#### List wish lists

**`GET /wishlists`**: Get list of wish lists of the current user.

#### Get details of a wish list

**`GET /wishlists/{id}`**: Get a single wish list by given ID. If the ID is `0` then the details of the default wish list are returned. The
current user must have permission to read the wish list.

#### List the items of a wish list

**`GET /wishlists/{id}/products`**: List the products of a single wish list identified by `id`. If the ID is `0` then the details of the
default wish list are returned. The current user must have permission to read the wish list.

#### Add product to a wish list

**`POST /wishlists/{id}/product/{product_id}`**: Adds a product id to a wish list. No post payload is required. If the ID of the wish list
is `0` (e.g., `/wishlists/0/product/{product_id}`) then a new wish list is created. The current user must have permission to write the wish
list.

#### Remove product from a wish list

**`DELETE /wishlists/{wish_list_id}/product/{product_id}`**: Removes a product id from a wish list. The current user must have permission to
write the wish list. The endpoint can have an optional request parameter `add_to_cart`. If the value of this parameter is `1` then the
product is transferred to the user's cart before removing it from the wish list. The product is not removed from the wish list, if the
product cannot be added to the cart.
