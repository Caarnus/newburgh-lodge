<script setup>
import AppLayout from "@/Layouts/AppLayout.vue";
import { computed, onMounted, reactive, ref, watch } from "vue";
import { Link, usePage } from "@inertiajs/vue3";
import { useMerchandiseCart } from "@/Composables/useMerchandiseCart";

import InputNumber from "primevue/inputnumber";
import Select from "primevue/select";
import Button from "primevue/button";
import Tag from "primevue/tag";
import Dialog from "primevue/dialog";

const page = usePage();
const items = computed(() => page.props.items ?? []);

const addSelections = reactive({});
const addQuantities = reactive({});
const addErrors = reactive({});

const lightboxVisible = ref(false);
const activePhotoItem = ref(null);

const { cartItems, hydrate, syncWithCatalogItems, addFromCatalog } = useMerchandiseCart();

const cartItemCount = computed(() =>
    cartItems.value.reduce((sum, item) => sum + Number(item.quantity || 0), 0)
);

const cartTotalCents = computed(() =>
    cartItems.value.reduce((sum, item) => sum + Number(item.price_cents || 0) * Number(item.quantity || 0), 0)
);

watch(
    items,
    (rows) => {
        for (const item of rows) {
            if (typeof addQuantities[item.id] === "undefined") {
                addQuantities[item.id] = 1;
            }

            if (item.requires_size && typeof addSelections[item.id] === "undefined") {
                addSelections[item.id] = "";
            }
        }

        syncWithCatalogItems(rows);
    },
    { immediate: true, deep: true }
);

onMounted(() => {
    hydrate();
    syncWithCatalogItems(items.value);
});

const formatCurrency = (cents) =>
    new Intl.NumberFormat("en-US", {
        style: "currency",
        currency: "USD",
    }).format((Number(cents) || 0) / 100);

const normalizeQuantity = (value) => {
    const parsed = Number(value ?? 0);
    if (!Number.isFinite(parsed) || parsed < 1) {
        return 1;
    }

    return Math.min(99, Math.floor(parsed));
};

const isSoldOut = (item) =>
    item.availability === "on_hand" && item.stock_remaining !== null && Number(item.stock_remaining) <= 0;

const openLightbox = (item) => {
    if (!item?.image_url) {
        return;
    }

    activePhotoItem.value = item;
    lightboxVisible.value = true;
};

const addItemToCart = (item) => {
    delete addErrors[item.id];

    if (isSoldOut(item)) {
        addErrors[item.id] = `${item.name} is currently sold out.`;
        return;
    }

    const quantity = normalizeQuantity(addQuantities[item.id]);
    const size = String(addSelections[item.id] ?? "").trim();

    if (item.requires_size && !size) {
        addErrors[item.id] = `Choose a size for ${item.name}.`;
        return;
    }

    const added = addFromCatalog(item, quantity, size || null);
    if (!added) {
        addErrors[item.id] = `${item.name} is currently unavailable.`;
        return;
    }

    addQuantities[item.id] = 1;
};
</script>

<template>
    <AppLayout title="Merchandise">
        <template #header>
            <h2 class="font-semibold text-xl leading-tight">
                Lodge Merchandise
            </h2>
        </template>

        <div class="py-12">
            <div class="max-w-7xl mx-auto sm:px-6 lg:px-8 space-y-6">
                <div class="bg-surface-0 shadow-xl sm:rounded-lg p-6 dark:bg-surface-900 dark:text-surface-100">
                    <h3 class="text-lg font-semibold">How Orders Work</h3>
                    <p class="mt-2 text-sm opacity-90">
                        Add items to your cart and continue to checkout. No payment information is accepted on the website.
                        We will follow up directly to confirm and coordinate fulfillment.
                    </p>

                    <div class="mt-4 flex flex-col sm:flex-row sm:items-center sm:justify-between gap-3">
                        <p class="text-sm opacity-90">
                            Cart: {{ cartItemCount }} item(s)
                            <span v-if="cartItemCount > 0"> | Estimated total: {{ formatCurrency(cartTotalCents) }}</span>
                        </p>
                        <Link :href="route('merchandise.checkout')">
                            <Button :label="cartItemCount > 0 ? 'Go to Checkout' : 'Checkout'" />
                        </Link>
                    </div>
                </div>

                <div class="bg-surface-0 shadow-xl sm:rounded-lg p-6 dark:bg-surface-900 dark:text-surface-100">
                    <h3 class="text-lg font-semibold">Shop Merchandise</h3>
                    <p class="mt-2 text-sm opacity-90">
                        On-hand and pre-order items are all submitted from one checkout form.
                    </p>

                    <div class="mt-4 grid grid-cols-1 sm:grid-cols-2 xl:grid-cols-3 gap-4 auto-rows-fr">
                        <article
                            v-for="item in items"
                            :key="item.id"
                            class="h-full rounded-lg border border-surface-200 dark:border-surface-700 bg-surface-50 dark:bg-surface-800 p-4 flex flex-col"
                        >
                            <button
                                type="button"
                                class="w-full h-48 rounded-md overflow-hidden border border-surface-200 dark:border-surface-700 bg-surface-100 dark:bg-surface-700"
                                @click="openLightbox(item)"
                            >
                                <img
                                    v-if="item.image_url"
                                    :src="item.image_url"
                                    :alt="`${item.name} photo`"
                                    class="h-full w-full object-cover"
                                />
                                <div v-else class="h-full w-full flex items-center justify-center text-sm opacity-70">
                                    No photo available
                                </div>
                            </button>

                            <div class="mt-3 flex items-start justify-between gap-3">
                                <h4 class="font-semibold">{{ item.name }}</h4>
                                <span class="font-semibold">{{ item.price_display }}</span>
                            </div>

                            <div class="mt-2 flex flex-wrap gap-2">
                                <Tag :value="item.availability_label" :severity="item.availability === 'preorder' ? 'info' : 'success'" />
                                <Tag v-if="item.is_limited_edition" value="Limited Edition" severity="warning" />
                            </div>

                            <p class="text-sm mt-2 opacity-90 min-h-12">{{ item.description }}</p>

                            <p
                                v-if="item.availability === 'on_hand' && item.stock_remaining !== null"
                                class="text-xs mt-2 font-medium"
                                :class="isSoldOut(item) ? 'text-red-700 dark:text-red-300' : 'text-amber-700 dark:text-amber-300'"
                            >
                                {{ isSoldOut(item) ? 'Sold out' : `Stock remaining: ${item.stock_remaining}` }}
                            </p>

                            <div class="mt-4 space-y-2">
                                <div v-if="item.requires_size">
                                    <label class="text-sm font-medium">Size</label>
                                    <Select
                                        v-model="addSelections[item.id]"
                                        :options="item.size_options"
                                        placeholder="Select size"
                                        class="w-full mt-1"
                                    />
                                </div>

                                <div>
                                    <label class="text-sm font-medium">Quantity</label>
                                    <InputNumber
                                        v-model="addQuantities[item.id]"
                                        :min="1"
                                        :max="99"
                                        :minFractionDigits="0"
                                        :maxFractionDigits="0"
                                        showButtons
                                        class="w-full mt-1"
                                    />
                                </div>
                            </div>

                            <small v-if="addErrors[item.id]" class="block mt-2 text-red-600 dark:text-red-400">
                                {{ addErrors[item.id] }}
                            </small>

                            <Button
                                class="mt-4 w-full"
                                :label="item.availability === 'preorder' ? 'Add pre-order item' : 'Add to cart'"
                                @click="addItemToCart(item)"
                                type="button"
                                :disabled="isSoldOut(item)"
                            />
                        </article>
                    </div>
                </div>
            </div>
        </div>

        <Dialog
            v-model:visible="lightboxVisible"
            modal
            :header="activePhotoItem?.name || 'Item Photo'"
            class="w-full sm:w-[48rem]"
        >
            <div v-if="activePhotoItem?.image_url" class="space-y-3">
                <img
                    :src="activePhotoItem.image_url"
                    :alt="`${activePhotoItem.name} photo`"
                    class="w-full rounded-lg"
                />
                <p class="text-sm opacity-90">{{ activePhotoItem.description }}</p>
                <div class="flex justify-end">
                    <Button label="Close" severity="secondary" @click="lightboxVisible = false" />
                </div>
            </div>
        </Dialog>
    </AppLayout>
</template>
