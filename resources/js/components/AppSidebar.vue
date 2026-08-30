<script setup lang="ts">
import NavFooter from '@/components/NavFooter.vue';
import NavMain from '@/components/NavMain.vue';
import NavUser from '@/components/NavUser.vue';
import { Sidebar, SidebarContent, SidebarFooter, SidebarHeader, SidebarMenu, SidebarMenuButton, SidebarMenuItem } from '@/components/ui/sidebar';
import { type NavItem } from '@/types';
import { Link, usePage } from '@inertiajs/vue3';
import { ArrowLeftRight, BarChart3, Building2, CalendarCheck, CalendarDays, CreditCard, LayoutGrid, Package, Settings2, Sparkles, UserCog, UserRoundSearch, UsersRound, Utensils } from 'lucide-vue-next';
import AppLogo from './AppLogo.vue';
import { computed } from 'vue';

const page = usePage();
// const isAdmin = computed(() => page.props.auth?.user?.role === 'admin');

const mainNavItems: NavItem[] = [
  {
    title: 'Dashboard',
    href: '/under-construction',
    icon: LayoutGrid,
  },

  {
    title: 'Hotel Management',
    icon: Building2,
    href: '/under-construction',
    // children: [
    //   {
    //     title: 'Menu #1',
    //     href: '/verify-eligibility',
    //     icon: UserCheck,
    //   },
    //   {
    //     title: 'Menu #2',
    //     href: '/claim-eligibility',
    //     icon: LucideNotebookText,
    //   },
    // ],
  },

  {
    title: 'Guest Management',
    icon: UsersRound,
    href: '/under-construction',
    // children: [
    //   {
    //     title: 'Menu #1',
    //     href: '/verify-member',
    //     icon: UserRoundSearch,
    //   },
    //   {
    //     title: 'Menu #2',
    //     href: '#',
    //     icon: LucideNotebookText,
    //   },
    // ],
  },

  {
    title: 'Reservation',
    icon: CalendarCheck,
    href: '/under-construction',
    // children: [
    //   {
    //     title: 'Menu #1',
    //     href: '/verify-member',
    //     icon: UserRoundSearch,
    //   },
    //   {
    //     title: 'Menu #2',
    //     href: '#',
    //     icon: LucideNotebookText,
    //   },
    // ],
  },

  {
    title: 'Check-in/Check-out',
    icon: ArrowLeftRight,
    href: '/under-construction',
    // children: [
    //   {
    //     title: 'Menu #1',
    //     href: '/verify-member',
    //     icon: UserRoundSearch,
    //   },
    //   {
    //     title: 'Menu #2',
    //     href: '#',
    //     icon: LucideNotebookText,
    //   },
    // ],
  },

  {
    title: 'Restaurant/POS',
    icon: Utensils,
    href: '/under-construction',
    // children: [
    //   {
    //     title: 'Menu #1',
    //     href: '/verify-member',
    //     icon: UserRoundSearch,
    //   },
    //   {
    //     title: 'Menu #2',
    //     href: '#',
    //     icon: LucideNotebookText,
    //   },
    // ],
  },

  {
    title: 'Events Management',
    icon: CalendarDays,
    href: '/under-construction',
    // children: [
    //   {
    //     title: 'Menu #1',
    //     href: '/verify-member',
    //     icon: UserRoundSearch,
    //   },
    //   {
    //     title: 'Menu #2',
    //     href: '#',
    //     icon: LucideNotebookText,
    //   },
    // ],
  },

  {
    title: 'Billing and Payments',
    icon: CreditCard,
    href: '/under-construction',
    // children: [
    //   {
    //     title: 'Menu #1',
    //     href: '/verify-member',
    //     icon: UserRoundSearch,
    //   },
    //   {
    //     title: 'Menu #2',
    //     href: '#',
    //     icon: LucideNotebookText,
    //   },
    // ],
  },

  {
    title: 'Inventory',
    icon: Package,
    href: '/under-construction',
    // children: [
    //   {
    //     title: 'Menu #1',
    //     href: '/verify-member',
    //     icon: UserRoundSearch,
    //   },
    //   {
    //     title: 'Menu #2',
    //     href: '#',
    //     icon: LucideNotebookText,
    //   },
    // ],
  },

  {
    title: 'Housekeeping',
    icon: Sparkles,
    href: '/under-construction',
    // children: [
    //   {
    //     title: 'Menu #1',
    //     href: '/verify-member',
    //     icon: UserRoundSearch,
    //   },
    //   {
    //     title: 'Menu #2',
    //     href: '#',
    //     icon: LucideNotebookText,
    //   },
    // ],
  },


  {
    title: 'Reports and Analytics',
    icon: BarChart3,
    href: '/under-construction',
    // children: [
    //   {
    //     title: 'Menu #1',
    //     href: '/verify-member',
    //     icon: UserRoundSearch,
    //   },
    //   {
    //     title: 'Menu #2',
    //     href: '#',
    //     icon: LucideNotebookText,
    //   },
    // ],
  },

  {
    title: 'User Management',
    icon: UserCog,
    href: '/under-construction',
    // children: [
    //   {
    //     title: 'Menu #1',
    //     href: '/verify-member',
    //     icon: UserRoundSearch,
    //   },
    //   {
    //     title: 'Menu #2',
    //     href: '#',
    //     icon: LucideNotebookText,
    //   },
    // ],
  },
  {
    title: 'Library',
    icon: Settings2,
    href: '/under-construction',

    children: [
      {
        title: 'Room Types',
        href: '/under-construction',
        icon: UserRoundSearch,
      },
      //   // {
      //   //   title: 'Menu #2',
      //   //   href: '#',
      //   //   icon: LucideNotebookText,
      //   // },
    ],
  },

];

// POS users only see the Point of Sale section
// const visibleNavItems = computed(() =>
//   isAdmin.value ? mainNavItems : mainNavItems.filter(item =>
//     item.title === 'Point of Sale'
//   )
// );
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
      <NavMain :items="mainNavItems" />
    </SidebarContent>

    <SidebarFooter>
      <NavUser />
    </SidebarFooter>
  </Sidebar>
  <slot />
</template>
