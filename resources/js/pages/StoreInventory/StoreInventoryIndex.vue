<script setup>
import { Button } from "@/components/ui/button";
import AppLayout from "@/layouts/AppLayout.vue";
import BaseIndex from "@/components/BaseIndex.vue";
import { ref, computed } from "vue";
import { toast } from "vue-sonner";
import { router, Head } from "@inertiajs/vue3";
import { Pill, Tag } from "lucide-vue-next";

import CreateProduct from "@/pages/Products/CreateProduct.vue";
import DeleteProduct from "@/pages/Products/DeleteProduct.vue";
import UpdatePosQty from "@/pages/StoreInventory/UpdatePosQty.vue";
import PosProductLotsView from '@/pages/StoreInventory/PosProductLotsView.vue';
import StoreProductHistory from "@/pages/StoreInventory/StoreProductHistory.vue";
import PricingHistory from "@/pages/StoreInventory/PricingHistory.vue";

const breadcrumbs = [
    { title: "Dashboard", href: "/dashboard" },
    { title: "Store Inventory", href: "/store-inventory" },
];

const props = defineProps({
    products: { required: true },
    columns: { type: Array, required: true },
    brands: { type: Array, default: () => [] },
    productUnits: { type: Array, default: () => [] },
    strengths: { type: Array, default: () => [] },
    drugforms: { type: Array, default: () => [] },
});

const selectOptions = props.columns
    .filter(col => col.isParameter === true)
    .map(s => ({ value: s.accessorKey, label: s.header }));

const selectModelValue = ref(selectOptions.length > 0 ? selectOptions[0].value : "");

const transformedColumns = computed(() =>
    props.columns.filter(col => col.isVisible === true)
);

const showCreateProductModal = ref(false);
const showDeleteProductModal = ref(false);
const showInitialPosQtyModal = ref(false);
const showLotsModal = ref(false);
const showHistoryModal = ref(false);
const showPricingHistoryModal = ref(false);
const selectedProduct = ref(null);

const currentType = ref(new URLSearchParams(window.location.search).get("type") || "all");

const handleTypeFilter = (type) => {
    currentType.value = type;
    const currentUrl = new URL(window.location.href);
    if (type === "all") {
        currentUrl.searchParams.delete("type");
    } else {
        currentUrl.searchParams.set("type", type);
    }
    currentUrl.searchParams.delete("page");
    router.get(currentUrl.pathname + currentUrl.search, {}, { preserveState: true });
};

const handleAction = ({ type, data }) => {
    switch (type) {
        case "initial":
            showInitialPosQtyModal.value = true;
            selectedProduct.value = data;
            break;
        case "viewlots":
            showLotsModal.value = true;
            selectedProduct.value = data;
            break;
        case "history":
            showHistoryModal.value = true;
            selectedProduct.value = data;
            break;
        case "pricing_history":
            showPricingHistoryModal.value = true;
            selectedProduct.value = data;
            break;
        case "delete":
            showDeleteProductModal.value = true;
            selectedProduct.value = data;
            break;
        default:
            break;
    }
};
</script>

<template>

    <Head title="Store Inventory" />
    <AppLayout :breadcrumbs="breadcrumbs">
        <div class="flex h-full flex-1 flex-col gap-4 rounded-xl p-4">
            <BaseIndex IndexType="StoreInventory" :data="products" :columnDefs="transformedColumns"
                :selectOptions="selectOptions" :row-class="(row) => {
                    if (!row.is_inventory) return 'bg-yellow-50 dark:bg-yellow-950';
                    return '';
                }" v-model:selectModelValue="selectModelValue" @action="handleAction" :hover-fields="[
                    { field: 'productname', label: 'Product Name' },
                    { field: 'brand_name', label: 'Brand' },
                ]">

                <Button variant="default" class="mr-2" @click="showInitialPosQtyModal = true">
                    New Product
                </Button>

                <div class="flex items-center gap-1 ml-2 border rounded-md p-1">
                    <Button :variant="currentType === 'all' ? 'default' : 'ghost'" size="sm"
                        @click="handleTypeFilter('all')">
                        All
                    </Button>
                    <Button :variant="currentType === 'generic' ? 'default' : 'ghost'" size="sm" class="gap-1"
                        @click="handleTypeFilter('generic')">
                        <Pill class="h-4 w-4" /> Generic
                    </Button>
                    <Button :variant="currentType === 'branded' ? 'default' : 'ghost'" size="sm" class="gap-1"
                        @click="handleTypeFilter('branded')">
                        <Tag class="h-4 w-4" /> Branded
                    </Button>
                </div>
            </BaseIndex>

            <CreateProduct v-if="showCreateProductModal" @form-closed="showCreateProductModal = false" :brands="brands"
                :product-units="productUnits" :strengths="strengths" :drugforms="drugforms" />

            <DeleteProduct v-if="showDeleteProductModal" :product="selectedProduct"
                @product-form-closed="showDeleteProductModal = false" />

            <UpdatePosQty v-if="showInitialPosQtyModal" @form-closed="showInitialPosQtyModal = false" />

            <PosProductLotsView v-if="showLotsModal" :product="selectedProduct" @form-closed="showLotsModal = false" />

            <StoreProductHistory v-if="showHistoryModal" :product="selectedProduct"
                @form-closed="showHistoryModal = false" />

            <PricingHistory v-if="showPricingHistoryModal" :product="selectedProduct"
                @form-closed="showPricingHistoryModal = false" />
        </div>
    </AppLayout>
</template>
