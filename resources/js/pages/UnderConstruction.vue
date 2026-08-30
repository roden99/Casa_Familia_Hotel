<script setup lang="ts">
import AppLayout from '@/layouts/AppLayout.vue';
import BaseEmpty from '@/components/BaseEmpty.vue';
import { type BreadcrumbItem } from '@/types';
import { Head } from '@inertiajs/vue3'

const breadcrumbs: BreadcrumbItem[] = [
    {
        title: 'Under Construction',
        href: '/under-construction',


    },
];
</script>


<template>

    <Head title="Under Construction" />
    <AppLayout :breadcrumbs="breadcrumbs">
        <div class="flex items-center justify-center min-h-[calc(100vh-200px)]">
            <BaseEmpty />
        </div>
    </AppLayout>
</template>
