<script setup>
import { Button } from '@/components/ui/button';
import AppLayout from '@/layouts/AppLayout.vue';
import BaseIndex from '@/components/BaseIndex.vue';
import { ref, computed } from 'vue';
import { Head } from '@inertiajs/vue3';

import CreateUser from '@/pages/Users/CreateUser.vue';
import UpdateUser from '@/pages/Users/UpdateUser.vue';
import DeleteUser from '@/pages/Users/DeleteUser.vue';

const breadcrumbs = [
    { title: 'Dashboard', href: '/dashboard' },
    { title: 'Users', href: '/users' },
];

const props = defineProps({
    users:   { required: true },
    columns: { type: Array, required: true },
});

const selectOptions = props.columns
    .filter(col => col.isParameter === true)
    .map(s => ({ value: s.accessorKey, label: s.header }));

const selectModelValue = ref(selectOptions.length > 0 ? selectOptions[0].value : '');

const transformedColumns = computed(() =>
    props.columns.filter(col => col.isVisible === true)
);

const showCreateModal = ref(false);
const showUpdateModal = ref(false);
const showDeleteModal = ref(false);
const selectedUser = ref(null);

const handleAction = ({ type, data }) => {
    selectedUser.value = data;
    if (type === 'edit')   showUpdateModal.value = true;
    if (type === 'delete') showDeleteModal.value = true;
};
</script>

<template>
    <Head title="Users" />
    <AppLayout :breadcrumbs="breadcrumbs">
        <div class="flex h-full flex-1 flex-col gap-4 rounded-xl p-4">
            <BaseIndex IndexType="Users" :data="users" :columnDefs="transformedColumns"
                :selectOptions="selectOptions" v-model:selectModelValue="selectModelValue"
                @action="handleAction">

                <Button variant="default" class="mr-2" @click="showCreateModal = true">
                    New User
                </Button>
            </BaseIndex>

            <CreateUser v-if="showCreateModal" @form-closed="showCreateModal = false" />

            <UpdateUser v-if="showUpdateModal && selectedUser" :user="selectedUser"
                @form-closed="showUpdateModal = false" />

            <DeleteUser v-if="showDeleteModal && selectedUser" :user="selectedUser"
                @form-closed="showDeleteModal = false" />
        </div>
    </AppLayout>
</template>
