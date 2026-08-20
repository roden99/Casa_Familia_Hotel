<script setup lang="ts">
import NavFooter from '@/components/NavFooter.vue';
import NavMain from '@/components/NavMain.vue';
import NavUser from '@/components/NavUser.vue';
import { Sidebar, SidebarContent, SidebarFooter, SidebarHeader, SidebarMenu, SidebarMenuButton, SidebarMenuItem } from '@/components/ui/sidebar';
import { type NavItem } from '@/types';
import { Link, usePage } from '@inertiajs/vue3';
import { BookOpen, Warehouse, Folder, Settings2Icon, UserRoundCogIcon, Store, UsersRoundIcon, NotebookText, ShoppingCartIcon, BaggageClaim, LayoutGrid, LucideNotebookText, ScanBarcode, Package, UserCheck, UserRoundSearch, UsersRound, ShoppingCart, UserRoundCog, Settings2, Truck, ClipboardList, Ruler, Zap, Pill, Notebook, BriefcaseMedical, CalendarClock, BarChart2, CreditCard, Tag, Undo2, PackageCheck, PackageMinus } from 'lucide-vue-next';
import AppLogo from './AppLogo.vue';
import { computed } from 'vue';

const page = usePage();
const isAdmin = computed(() => page.props.auth?.user?.role === 'admin');

const mainNavItems: NavItem[] = [
  {
    title: 'Dashboard',
    href: '/dashboard',
    icon: LayoutGrid,
  },


  {
    title: 'Point of Sale',
    icon: ScanBarcode,
    href: '/under-construction',
    children: [
      {
        title: 'POS Dashboard',
        href: '/pos-dashboard',
        icon: BarChart2,
      },
      {
        title: 'Store Inventory',
        href: '/store-inventory',
        icon: ScanBarcode,
      },
      {
        title: 'Transfer Stock',
        href: '/transfer-stocks',
        icon: Package,
      },

      {
        title: 'POS Transactions',
        href: '/pos',
        icon: ShoppingCart,
      },

      {
        title: 'POS Deliveries',
        href: '/pos-deliveries',
        icon: Truck,
      },

      {
        title: 'POS Items',
        href: '/pos-items',
        icon: Tag,
      },

      //   {
      //     title: 'Menu #2',
      //     href: '/under-construction',
      //     icon: LucideNotebookText,
      //   },
    ],
  },

  {

    title: 'Inventory',
    href: '/products',
    icon: Package,

  },
  {
    title: 'Expirations',
    href: '/expirations',
    icon: CalendarClock,
  },

  {

    title: 'Stock Receiving',
    href: '/deliveries',
    icon: Truck,


  },

  {
    title: 'Sales Orders',
    href: '/sales-orders',
    icon: Package,

  },

  {
    title: 'Return Good Stock',
    href: '/return-good-stocks',
    icon: PackageCheck,
  },

  {
    title: 'Return to Supplier',
    href: '/return-to-suppliers',
    icon: PackageMinus,
  },

  {
    title: 'Carry Items',
    href: '/carry-items',
    icon: BriefcaseMedical,
  },

  {
    title: 'Customer Accounts',
    href: '/customer-accounts',
    icon: UserCheck,
  },




  // {
  //   title: 'Stock Movement',
  //   icon: BaggageClaim,
  //   href: '/under-construction',
  // },

  {
    title: 'Reports',
    icon: NotebookText,
    href: '/under-construction',
  },

  {
    title: 'Library',
    icon: Settings2,
    children: [
      // {
      //   title: 'Products',
      //   href: '/products',
      //   icon: Package,
      // },
      {
        title: 'Product Units',
        href: '/product-units',
        icon: Ruler,
      },
      {
        title: 'Brands',
        href: '/brands',
        icon: NotebookText,
      },
      // {
      //   title: 'Strengths',
      //   href: '/strengths',
      //   icon: Zap,
      // },
      {
        title: 'Drug Forms',
        href: '/drugforms',
        icon: Pill,
      },
      {
        title: 'Product Types',
        href: '/product-types',
        icon: BriefcaseMedical,
      },
      {
        title: 'Warehouse',
        href: '/warehouses',
        icon: Warehouse,
      },
      {
        title: 'Customers',
        href: '/customers',
        icon: UsersRoundIcon,
      },
      {
        title: 'Suppliers',
        href: '/suppliers',
        icon: Store,
      },
      {
        title: 'Sales Accounts',
        href: '/sales-accounts',
        icon: Notebook,
      },
      {
        title: 'Sales Agents',
        href: '/sales-agents',
        icon: UserRoundSearch,
      },
      {
        title: 'User Management',
        href: '/users',
        icon: UserRoundCog,
      },
    ],
  },

];

// POS users only see the Point of Sale section
const visibleNavItems = computed(() =>
  isAdmin.value ? mainNavItems : mainNavItems.filter(item =>
    item.title === 'Point of Sale'
  )
);
</script>

<template>
  <Sidebar collapsible="icon" variant="inset">
    <SidebarHeader>
      <SidebarMenu>
        <SidebarMenuItem>
          <SidebarMenuButton size="lg" as-child>
            <Link :href="route('dashboard')">
              <AppLogo />
            </Link>
          </SidebarMenuButton>
        </SidebarMenuItem>
      </SidebarMenu>
    </SidebarHeader>

    <SidebarContent>
      <NavMain :items="visibleNavItems" />
    </SidebarContent>

    <SidebarFooter>
      <NavUser />
    </SidebarFooter>
  </Sidebar>
  <slot />
</template>
