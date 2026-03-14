/**
 * Wishlist JavaScript - Server-side only
 * All wishlist operations are handled by the backend
 */

class WishlistManager {
    constructor() {
        this.init();
    }

    init() {
        this.bindEvents();
        this.updateWishlistCount();
        this.updateWishlistButtons();
    }

    /**
     * Bind event listeners
     */
    bindEvents() {
        // Wishlist toggle buttons
        document.addEventListener("click", (e) => {
            if (e.target.closest("[data-wishlist-toggle]")) {
                e.preventDefault();
                const button = e.target.closest("[data-wishlist-toggle]");
                const productId = button.getAttribute("data-product-id");

                this.handleWishlistToggle(productId, button);
            }
        });

        // Clear wishlist button - disabled to avoid conflict with inline script
        // document.addEventListener("click", (e) => {
        //     if (e.target.closest("#clear-wishlist-btn")) {
        //         e.preventDefault();
        //         this.handleClearWishlist();
        //     }
        // });
    }

    /**
     * Handle wishlist toggle
     */
    handleWishlistToggle(productId, button) {
        // Show loading state
        button.disabled = true;
        button.style.opacity = "0.5";

        // Send request to server
        fetch("/wishlist/toggle", {
            method: "POST",
            headers: {
                "Content-Type": "application/json",
                "X-CSRF-TOKEN": document
                    .querySelector('meta[name="csrf-token"]')
                    .getAttribute("content"),
            },
            body: JSON.stringify({
                product_id: productId,
            }),
        })
            .then((response) => response.json())
            .then((data) => {
                if (data.success) {
                    // Track Facebook Pixel AddToWishlist event (only if item was added)
                    if (data.action === "added" && typeof fbq !== "undefined") {
                        const productName =
                            button.getAttribute("data-product-name");
                        const productPrice =
                            button.getAttribute("data-product-price");

                        fbq("track", "AddToWishlist", {
                            content_name: productName,
                            content_ids: [productId],
                            content_type: "product",
                            value: productPrice,
                            currency: "USD",
                        });
                    }

                    if (
                        data.action === "added" &&
                        typeof window !== "undefined" &&
                        window.ttq
                    ) {
                        const productName =
                            button.getAttribute("data-product-name") || "";
                        const rawPrice =
                            button.getAttribute("data-product-price") || "0";
                        const numericPrice = Number(rawPrice) || 0;

                        const tiktokWishlistPayload = {
                            contents: [
                                {
                                    content_id: String(productId),
                                    content_type: "product",
                                    content_name: productName,
                                },
                            ],
                            value: numericPrice,
                            currency: "USD",
                        };

                        if (numericPrice) {
                            tiktokWishlistPayload.contents[0].price = numericPrice;
                        }

                        window.ttq.track(
                            "AddToWishlist",
                            tiktokWishlistPayload
                        );
                    }

                    // Show success message
                    this.showMessage(data.message, "success");

                    // Update button state
                    this.updateWishlistButtons();

                    // Update count
                    this.updateWishlistCount();

                    // Add animation
                    button.style.transform = "scale(1.2)";
                    setTimeout(() => {
                        button.style.transform = "scale(1)";
                        button.disabled = false;
                        button.style.opacity = "1";
                    }, 200);

                    // Dispatch custom event for other components
                    window.dispatchEvent(new CustomEvent("wishlistUpdated"));
                    // Promo popup: "Get 10% OFF! Enter your email..."
                    if (typeof window.promoPopupShow === "function") {
                        setTimeout(function () {
                            window.promoPopupShow("wishlist");
                        }, 400);
                    }
                } else {
                    this.showMessage(data.message, "error");
                    button.disabled = false;
                    button.style.opacity = "1";
                }
            })
            .catch((error) => {
                console.error("Error:", error);
                this.showMessage(
                    "An error occurred while updating wishlist.",
                    "error"
                );
                button.disabled = false;
                button.style.opacity = "1";
            });
    }

    /**
     * Handle clear wishlist
     */
    handleClearWishlist() {
        // Check if modal exists on the page
        const clearModal = document.getElementById("clear-wishlist-modal");
        if (clearModal) {
            // Show the modal instead of using confirm
            clearModal.classList.remove("hidden");
        } else {
            // Fallback: show custom confirmation
            this.showClearConfirmation();
        }
    }

    /**
     * Show custom clear confirmation
     */
    showClearConfirmation() {
        // Create custom modal if it doesn't exist
        const modal = document.createElement("div");
        modal.id = "clear-wishlist-modal";
        modal.className =
            "fixed inset-0 bg-black bg-opacity-50 z-50 flex items-center justify-center";
        modal.innerHTML = `
            <div class="bg-white rounded-lg p-6 max-w-md w-full mx-4">
                <div class="flex items-center mb-4">
                    <div class="w-12 h-12 bg-red-100 rounded-full flex items-center justify-center mr-4">
                        <svg class="w-6 h-6 text-red-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-2.5L13.732 4c-.77-.833-1.964-.833-2.732 0L3.732 16.5c-.77.833.192 2.5 1.732 2.5z"></path>
                        </svg>
                    </div>
                    <div>
                        <h3 class="text-lg font-semibold text-gray-900">Clear All Wishlist</h3>
                        <p class="text-sm text-gray-600">This action cannot be undone</p>
                    </div>
                </div>
                <p class="text-gray-700 mb-6">Are you sure you want to remove all products from your wishlist?</p>
                <div class="flex space-x-3">
                    <button id="confirm-clear-wishlist" 
                            class="flex-1 bg-red-600 hover:bg-red-700 text-white py-2 px-4 rounded-lg transition-colors">
                        Clear All
                    </button>
                    <button id="cancel-clear-wishlist" 
                            class="flex-1 bg-gray-300 hover:bg-gray-400 text-gray-700 py-2 px-4 rounded-lg transition-colors">
                        Cancel
                    </button>
                </div>
            </div>
        `;

        // Add to body
        document.body.appendChild(modal);

        // Handle confirm button
        const confirmBtn = modal.querySelector("#confirm-clear-wishlist");
        const cancelBtn = modal.querySelector("#cancel-clear-wishlist");

        confirmBtn.addEventListener("click", () => {
            this.executeClearWishlist();
            modal.remove();
        });

        cancelBtn.addEventListener("click", () => {
            modal.remove();
        });

        // Close on outside click
        modal.addEventListener("click", (e) => {
            if (e.target === modal) {
                modal.remove();
            }
        });
    }

    /**
     * Execute clear wishlist request
     */
    executeClearWishlist() {
        // Send request to server
        fetch("/wishlist/clear", {
            method: "POST",
            headers: {
                "Content-Type": "application/json",
                "X-CSRF-TOKEN": document
                    .querySelector('meta[name="csrf-token"]')
                    .getAttribute("content"),
            },
        })
            .then((response) => response.json())
            .then((data) => {
                if (data.success) {
                    this.showMessage(data.message, "success");
                    // Reload page to update wishlist display
                    setTimeout(() => {
                        location.reload();
                    }, 1000);
                } else {
                    this.showMessage(data.message, "error");
                }
            })
            .catch((error) => {
                console.error("Error:", error);
                this.showMessage(
                    "An error occurred while clearing wishlist.",
                    "error"
                );
            });
    }

    /**
     * Update wishlist count display
     */
    updateWishlistCount() {
        // Get count from server
        fetch("/wishlist/count")
            .then((response) => response.json())
            .then((data) => {
                if (data.success) {
                    // Update wishlist count in header/navbar
                    const countElements =
                        document.querySelectorAll(".wishlist-count");
                    countElements.forEach((element) => {
                        element.textContent = data.count;
                        element.style.display =
                            data.count > 0 ? "flex" : "none";
                    });
                }
            })
            .catch((error) => {
                console.error("Error fetching wishlist count:", error);
            });
    }

    /**
     * Update wishlist button states
     */
    updateWishlistButtons() {
        // Get all product IDs on the page
        const buttons = document.querySelectorAll("[data-wishlist-toggle]");
        const productIds = Array.from(buttons).map((btn) =>
            btn.getAttribute("data-product-id")
        );

        if (productIds.length === 0) return;

        // Check wishlist status for all products
        fetch("/wishlist/check", {
            method: "POST",
            headers: {
                "Content-Type": "application/json",
                "X-CSRF-TOKEN": document
                    .querySelector('meta[name="csrf-token"]')
                    .getAttribute("content"),
            },
            body: JSON.stringify({
                product_ids: productIds,
            }),
        })
            .then((response) => response.json())
            .then((data) => {
                if (data.success) {
                    // Update button states based on server response
                    buttons.forEach((button) => {
                        const productId =
                            button.getAttribute("data-product-id");
                        const isInWishlist = data.wishlist_items.includes(
                            parseInt(productId)
                        );

                        if (isInWishlist) {
                            button.classList.add("in-wishlist");
                            button.classList.remove("not-in-wishlist");
                            // Update button text if it has text
                            const textSpan = button.querySelector("span");
                            if (textSpan) {
                                textSpan.textContent = "Remove from Wishlist";
                            }
                        } else {
                            button.classList.add("not-in-wishlist");
                            button.classList.remove("in-wishlist");
                            // Update button text if it has text
                            const textSpan = button.querySelector("span");
                            if (textSpan) {
                                textSpan.textContent = "Add to Wishlist";
                            }
                        }
                    });
                }
            })
            .catch((error) => {
                console.error("Error checking wishlist status:", error);
            });
    }

    /**
     * Show message to user
     */
    showMessage(message, type = "success") {
        // Create message element
        const messageEl = document.createElement("div");
        messageEl.className = `fixed top-4 right-4 z-50 px-6 py-3 rounded-lg shadow-lg transform transition-all duration-300 ${
            type === "success"
                ? "bg-green-500 text-white"
                : "bg-red-500 text-white"
        }`;
        messageEl.textContent = message;

        // Add to DOM
        document.body.appendChild(messageEl);

        // Animate in
        setTimeout(() => {
            messageEl.style.transform = "translateY(0)";
        }, 100);

        // Remove after 3 seconds
        setTimeout(() => {
            messageEl.style.transform = "translateY(-100px)";
            messageEl.style.opacity = "0";
            setTimeout(() => {
                messageEl.remove();
            }, 300);
        }, 3000);
    }

    /**
     * Sync with server on login
     */
    syncWithServer() {
        fetch("/wishlist/transfer", {
            method: "POST",
            headers: {
                "Content-Type": "application/json",
                "X-CSRF-TOKEN": document
                    .querySelector('meta[name="csrf-token"]')
                    .getAttribute("content"),
            },
        })
            .then((response) => response.json())
            .then((data) => {
                if (data.success) {
                    console.log("Wishlist synced with server");
                    this.updateWishlistCount();
                    this.updateWishlistButtons();
                }
            })
            .catch((error) => {
                console.error("Error syncing wishlist:", error);
            });
    }
}

// Initialize wishlist manager when DOM is ready
document.addEventListener("DOMContentLoaded", function () {
    window.wishlistManager = new WishlistManager();
});
