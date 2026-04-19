import { ref, watch } from "vue";

const STORAGE_KEY = "newburgh_lodge_merchandise_cart_v1";

const normalizeSize = (value) => {
    const size = String(value ?? "").trim();
    return size.length > 0 ? size : null;
};

const maxQuantityForItem = (item) => {
    const rawStock = item?.stock_remaining;
    if (rawStock === null || typeof rawStock === "undefined" || rawStock === "") {
        return 99;
    }

    const stockRemaining = Number(rawStock);
    if (Number.isFinite(stockRemaining) && stockRemaining >= 0) {
        return Math.max(0, Math.floor(stockRemaining));
    }

    return 99;
};

const normalizeQuantity = (value, max = 99) => {
    const parsed = Number(value ?? 0);
    if (!Number.isFinite(parsed)) {
        return 1;
    }

    return Math.min(Math.max(Math.floor(parsed), 1), Math.max(1, max));
};

const lineKey = (itemId, size) => `${Number(itemId) || 0}::${normalizeSize(size) ?? ""}`;

const normalizeStoredItem = (item) => ({
    id: Number(item?.id ?? 0),
    name: String(item?.name ?? ""),
    description: String(item?.description ?? ""),
    image_url: item?.image_url ?? null,
    availability: String(item?.availability ?? "on_hand"),
    availability_label: String(item?.availability_label ?? ""),
    price_cents: Number(item?.price_cents ?? 0),
    price_display: String(item?.price_display ?? "$0.00"),
    requires_size: Boolean(item?.requires_size),
    size_options: Array.isArray(item?.size_options) ? item.size_options.map((v) => String(v)) : [],
    is_limited_edition: Boolean(item?.is_limited_edition),
    stock_remaining: item?.stock_remaining === null || typeof item?.stock_remaining === "undefined"
        ? null
        : Number(item.stock_remaining),
    quantity: normalizeQuantity(item?.quantity ?? 1),
    size: normalizeSize(item?.size),
});

export const useMerchandiseCart = () => {
    const cartItems = ref([]);
    const hydrated = ref(false);

    const hydrate = () => {
        if (hydrated.value || typeof window === "undefined") {
            return;
        }

        hydrated.value = true;

        try {
            const raw = window.localStorage.getItem(STORAGE_KEY);
            if (!raw) {
                cartItems.value = [];
                return;
            }

            const parsed = JSON.parse(raw);
            if (!Array.isArray(parsed)) {
                cartItems.value = [];
                return;
            }

            cartItems.value = parsed
                .map((entry) => normalizeStoredItem(entry))
                .filter((entry) => entry.id > 0);
        } catch (_error) {
            cartItems.value = [];
        }
    };

    watch(
        cartItems,
        (items) => {
            if (!hydrated.value || typeof window === "undefined") {
                return;
            }

            window.localStorage.setItem(STORAGE_KEY, JSON.stringify(items));
        },
        { deep: true }
    );

    const syncWithCatalogItems = (catalogItems) => {
        const byId = new Map((catalogItems ?? []).map((item) => [Number(item.id), item]));
        cartItems.value = cartItems.value
            .map((line) => {
                const currentItem = byId.get(Number(line.id));
                if (!currentItem) {
                    return null;
                }

                const maxQty = maxQuantityForItem(currentItem);
                if (maxQty === 0 && currentItem.availability === "on_hand") {
                    return null;
                }

                return {
                    ...line,
                    ...normalizeStoredItem({
                        ...currentItem,
                        quantity: normalizeQuantity(line.quantity, maxQty || 99),
                        size: line.size,
                    }),
                };
            })
            .filter((line) => line !== null);
    };

    const addFromCatalog = (catalogItem, quantity = 1, size = null) => {
        const normalizedItem = normalizeStoredItem({
            ...catalogItem,
            quantity: quantity,
            size: size,
        });

        const maxQty = maxQuantityForItem(catalogItem);
        if (catalogItem?.availability === "on_hand" && maxQty <= 0) {
            return false;
        }

        const key = lineKey(normalizedItem.id, normalizedItem.size);
        const existingIndex = cartItems.value.findIndex((line) => lineKey(line.id, line.size) === key);

        if (existingIndex >= 0) {
            const existing = cartItems.value[existingIndex];
            const nextQty = normalizeQuantity((existing.quantity ?? 0) + normalizedItem.quantity, maxQty || 99);
            cartItems.value[existingIndex] = {
                ...existing,
                ...normalizedItem,
                quantity: nextQty,
            };

            return true;
        }

        cartItems.value.push(normalizedItem);
        return true;
    };

    const removeAt = (index) => {
        if (index < 0 || index >= cartItems.value.length) {
            return;
        }

        cartItems.value.splice(index, 1);
    };

    const updateQuantity = (index, quantity) => {
        const line = cartItems.value[index];
        if (!line) {
            return;
        }

        const maxQty = line.availability === "on_hand" && line.stock_remaining !== null
            ? Math.max(1, Number(line.stock_remaining))
            : 99;

        cartItems.value[index].quantity = normalizeQuantity(quantity, maxQty);
    };

    const updateSize = (index, size) => {
        const line = cartItems.value[index];
        if (!line) {
            return;
        }

        cartItems.value[index].size = normalizeSize(size);
    };

    const clear = () => {
        cartItems.value = [];
    };

    return {
        cartItems,
        hydrate,
        syncWithCatalogItems,
        addFromCatalog,
        updateQuantity,
        updateSize,
        removeAt,
        clear,
    };
};
