<script setup>
import { ArrowUpRightIcon, Wrench, Phone, Mail } from 'lucide-vue-next'
import { Button } from '@/components/ui/buttonorig'
import {
    Empty,
    EmptyContent,
    EmptyDescription,
    EmptyHeader,
    EmptyMedia,
    EmptyTitle,
} from '@/components/ui/empty'
</script>
<template>
    <Empty class="h-auto py-6">
        <EmptyHeader class="gap-2">
            <EmptyMedia variant="icon" class="h-12 w-12">
                <Wrench class="h-6 w-6" />
            </EmptyMedia>
            <EmptyTitle class="text-2xl font-medium">✨ We're building something exciting!</EmptyTitle>

            <EmptyDescription class="text-base leading-tight">
                <div class="flex flex-col items-center gap-3">
                    <p class="mb-4"> This module is currently under development and will be available soon. </p>

                    <div class="flex w-full items-center justify-center gap-2">
                        <Phone class="h-4 w-4" /> <span>0975-3456-580</span>
                        <Mail class="h-4 w-4" /> <span>astraroden@gmail.com</span>
                    </div>

                </div>



            </EmptyDescription>



        </EmptyHeader>
    </Empty>
</template>