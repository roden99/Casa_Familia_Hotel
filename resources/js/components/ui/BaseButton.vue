<script setup>
import { Loader2 } from 'lucide-vue-next';
import { Button, buttonVariants } from '@/components/ui/buttonorig';
import { ColumnOrdering } from '@tanstack/vue-table';
import { computed } from 'vue';


const props = defineProps({
  transactionType: {
    type: String,
    default: 'create',
  },
  type: {
    type: String,
    default: 'button',
  },
  loading: {
    type: Boolean,
    default: false,
  },
  disabled: {
    type: Boolean,
    default: false,
  }
});

const buttonText = computed(() => {
  if (props.transactionType === 'create') return 'Save';
  if (props.transactionType === 'update') return 'Update';
  if (props.transactionType === 'delete') return 'Delete';
  if (props.transactionType === 'cancel') return 'Cancel';
  if (props.transactionType === 'verify') return 'Verify';
  if (props.transactionType === 'clear') return 'Clear';
  return 'Submit';
});

const buttonVariant = computed(() => {
  if (props.transactionType === 'delete') return 'destructive';
  if (props.transactionType === 'cancel') return 'secondary';
  if (props.transactionType === 'verify') return 'secondary';
  if (props.transactionType === 'clear') return 'secondary';
  return 'default';
});

const buttonColor = computed(() => {
  if (props.transactionType === 'delete') return 'destructive';
  if (props.transactionType === 'cancel') return 'secondary';
  if (props.transactionType === 'verify') return 'secondary';
  if (props.transactionType === 'clear') return 'secondary';

  return 'primary';
});

</script>
<template>

  <Button :variant="buttonVariant" :color="buttonColor" :type="type" :disabled="disabled" :loading="loading">
    <Loader2 v-if="loading" class="mr-2 h-4 w-4 animate-spin" />
    {{ loading ? 'Please wait' : buttonText }}
  </Button>
</template>