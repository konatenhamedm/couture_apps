'use client';

import React, { useState, useEffect } from 'react';
import Image from 'next/image';
import Link from 'next/link';
import { usePathname } from 'next/navigation';
import {
  LayoutDashboard,
  Users,
  Ruler,
  Calendar,
  DollarSign,
  Package,
  Store,
  BarChart3,
  Bell,
  Settings,
  ChevronLeft,
  ChevronRight
} from 'lucide-react';

interface MenuItem {
  title: string;
  icon: React.ReactNode;
  path?: string;
  subItems?: { title: string; path: string; roles?: string[] }[];
  roles?: string[];
}

interface SidebarProps {
  isOpen: boolean;
  onToggle: () => void;
  isCollapsed: boolean;
  onCollapsedChange: (collapsed: boolean) => void;
  userRole?: string; // SADM, ADB, ADS, ADSB
}

export default function AteliyaSidebar({ 
  isOpen, 
  onToggle, 
  isCollapsed, 
  onCollapsedChange,
  userRole = 'SADM' 
}: SidebarProps) {
  const [openMenus, setOpenMenus] = useState<string[]>([]);
  const pathname = usePathname();

  useEffect(() => {
    const shouldOpenMenus: string[] = [];
    menuItems.forEach(item => {
      if (item.subItems && item.subItems.some(subItem => isSubItemActive(subItem.path))) {
        shouldOpenMenus.push(item.title);
      }
    });
    if (shouldOpenMenus.length > 0) {
      setOpenMenus(shouldOpenMenus);
    }
  }, [pathname]);

  const menuItems: MenuItem[] = [
    {
      title: 'Dashboard',
      icon: <LayoutDashboard className="w-5 h-5" />,
      path: '/dashboard',
      roles: ['SADM', 'ADB', 'ADS', 'ADSB']
    },
    {
      title: 'Clients',
      icon: <Users className="w-5 h-5" />,
      path: '/clients',
      roles: ['SADM', 'ADB', 'ADS', 'ADSB']
    },
    {
      title: 'Mesures',
      icon: <Ruler className="w-5 h-5" />,
      roles: ['SADM', 'ADB', 'ADS', 'ADSB'],
      subItems: [
        { title: 'Catégories', path: '/mesures/categories', roles: ['SADM', 'ADB', 'ADSB'] },
        { title: 'Types de mesures', path: '/mesures/types', roles: ['SADM', 'ADB', 'ADSB'] },
        { title: 'Modèles vêtements', path: '/mesures/modeles', roles: ['SADM', 'ADB', 'ADSB'] },
        { title: 'Prendre mesures', path: '/mesures/prendre', roles: ['SADM', 'ADB', 'ADS', 'ADSB'] }
      ]
    },
    {
      title: 'Réservations',
      icon: <Calendar className="w-5 h-5" />,
      path: '/reservations',
      roles: ['SADM', 'ADB', 'ADS', 'ADSB'],
      subItems: [
        { title: 'Réservations du jour', path: '/reservations/aujourd-hui', roles: ['SADM', 'ADB', 'ADS', 'ADSB'] },
        { title: 'Retraits programmés', path: '/reservations/retraits', roles: ['SADM', 'ADB', 'ADS', 'ADSB'] }
      ]
    },
    {
      title: 'Finances',
      icon: <DollarSign className="w-5 h-5" />,
      roles: ['SADM', 'ADB', 'ADS', 'ADSB'],
      subItems: [
        { title: 'Factures', path: '/finances/factures', roles: ['SADM', 'ADS', 'ADSB'] },
        { title: 'Paiements factures', path: '/finances/paiements/factures', roles: ['SADM', 'ADS', 'ADSB'] },
        { title: 'Paiements réservations', path: '/finances/paiements/reservations', roles: ['SADM', 'ADB', 'ADS', 'ADSB'] },
        { title: 'Ventes boutique', path: '/finances/ventes', roles: ['SADM', 'ADB', 'ADSB'] },
        { title: 'Abonnements', path: '/finances/abonnements', roles: ['SADM'] },
        { title: 'Rapports', path: '/finances/rapports', roles: ['SADM', 'ADB', 'ADS', 'ADSB'] }
      ]
    },
    {
      title: 'Stocks',
      icon: <Package className="w-5 h-5" />,
      roles: ['SADM', 'ADB', 'ADSB'],
      subItems: [
        { title: 'Inventaire', path: '/stocks/inventaire', roles: ['SADM', 'ADB', 'ADSB'] },
        { title: 'Mouvements', path: '/stocks/mouvements', roles: ['SADM', 'ADB', 'ADSB'] },
        { title: 'Alertes stock', path: '/stocks/alertes', roles: ['SADM', 'ADB', 'ADSB'] },
        { title: 'Fournisseurs', path: '/stocks/fournisseurs', roles: ['SADM'] }
      ]
    },
    {
      title: 'Boutique',
      icon: <Store className="w-5 h-5" />,
      roles: ['SADM', 'ADB', 'ADSB'],
      subItems: [
        { title: 'Ma boutique', path: '/boutique/profil', roles: ['SADM', 'ADB', 'ADSB'] },
        { title: 'Catalogue vêtements', path: '/boutique/catalogue', roles: ['SADM', 'ADB', 'ADSB'] },
        { title: 'Modèles disponibles', path: '/boutique/modeles', roles: ['SADM', 'ADB', 'ADSB'] },
        { title: 'Caisses', path: '/boutique/caisses', roles: ['SADM', 'ADB', 'ADSB'] },
        { title: 'Succursales', path: '/boutique/succursales', roles: ['SADM'] },
        { title: 'Employés', path: '/boutique/employes', roles: ['SADM', 'ADB', 'ADSB'] },
        { title: 'Paramètres', path: '/boutique/parametres', roles: ['SADM'] }
      ]
    },
    {
      title: 'Statistiques',
      icon: <BarChart3 className="w-5 h-5" />,
      roles: ['SADM', 'ADB', 'ADS', 'ADSB'],
      subItems: [
        { title: 'Dashboard avancé', path: '/statistiques/dashboard', roles: ['SADM', 'ADB', 'ADS', 'ADSB'] },
        { title: 'Revenus', path: '/statistiques/revenus', roles: ['SADM', 'ADB', 'ADS', 'ADSB'] },
        { title: 'Clients', path: '/statistiques/clients', roles: ['SADM', 'ADB', 'ADS', 'ADSB'] },
        { title: 'Performance', path: '/statistiques/performance', roles: ['SADM'] },
        { title: 'Export données', path: '/statistiques/export', roles: ['SADM'] }
      ]
    },
    {
      title: 'Notifications',
      icon: <Bell className="w-5 h-5" />,
      path: '/notifications',
      roles: ['SADM', 'ADB', 'ADS', 'ADSB'],
      subItems: [
        { title: 'Paramètres push', path: '/notifications/push', roles: ['SADM', 'ADB', 'ADSB'] },
        { title: 'Templates email', path: '/notifications/templates', roles: ['SADM'] },
        { title: 'Historique', path: '/notifications/historique', roles: ['SADM', 'ADB', 'ADS', 'ADSB'] }
      ]
    },
    {
      title: 'Paramètres',
      icon: <Settings className="w-5 h-5" />,
      roles: ['SADM', 'ADB', 'ADS', 'ADSB'],
      subItems: [
        { title: 'Profil utilisateur', path: '/parametres/profil', roles: ['SADM', 'ADB', 'ADS', 'ADSB'] },
        { title: 'Sécurité', path: '/parametres/securite', roles: ['SADM', 'ADB', 'ADS', 'ADSB'] },
        { title: 'Préférences', path: '/parametres/preferences', roles: ['SADM', 'ADB', 'ADS', 'ADSB'] },
        { title: 'API & Intégrations', path: '/parametres/api', roles: ['SADM'] },
        { title: 'Sauvegarde', path: '/parametres/sauvegarde', roles: ['SADM'] }
      ]
    }
  ];

  // Filter menu items based on user role
  const filteredMenuItems = menuItems.filter(item => 
    !item.roles || item.roles.includes(userRole)
  ).map(item => ({
    ...item,
    subItems: item.subItems?.filter(subItem => 
      !subItem.roles || subItem.roles.includes(userRole)
    )
  }));

  const toggleMenu = (title: string) => {
    if (isCollapsed) {
      onCollapsedChange(false);
      setTimeout(() => {
        setOpenMenus([title]);
      }, 150);
    } else {
      setOpenMenus(prev =>
        prev.includes(title)
          ? prev.filter(item => item !== title)
          : [...prev, title]
      );
    }
  };

  const toggleCollapse = () => {
    onCollapsedChange(!isCollapsed);
    if (!isCollapsed) {
      setOpenMenus([]);
    }
  };

  const isActive = (path: string) => {
    return pathname === path || pathname.startsWith(path + '/');
  };

  const isParentActive = (item: MenuItem) => {
    if (item.path) {
      return isActive(item.path);
    }
    if (item.subItems) {
      return item.subItems.some(subItem => isSubItemActive(subItem.path));
    }
    return false;
  };

  const isSubItemActive = (path: string) => {
    return pathname === path;
  };

  const getRoleLabel = (role: string) => {
    const roleLabels = {
      'SADM': 'Super Administrateur',
      'ADB': 'Gérant Boutique',
      'ADS': 'Gérant Succursale',
      'ADSB': 'Gérant Succursale & Boutique'
    };
    return roleLabels[role as keyof typeof roleLabels] || role;
  };

  return (
    <>
      {/* Mobile Overlay */}
      {isOpen && (
        <div
          className="fixed inset-0 bg-purple-900/30 backdrop-blur-sm z-40 lg:hidden"
          onClick={onToggle}
        />
      )}

      {/* Sidebar */}
      <div className={`
        fixed top-0 left-0 h-full bg-gradient-to-b from-purple-50 via-white to-purple-50/30 shadow-2xl shadow-purple-900/10 z-50 transition-all duration-300 ease-in-out border-r border-purple-200
        ${isOpen ? 'translate-x-0' : '-translate-x-full lg:translate-x-0'}
        ${isCollapsed ? 'w-20' : 'w-64'}
      `}>
        {/* Logo Section */}
        <div className="p-4 border-b border-purple-200 bg-gradient-to-r from-white via-purple-50 to-white backdrop-blur-sm">
          <div className="flex items-center justify-between">
            {!isCollapsed && (
              <div className="flex items-center space-x-3 overflow-hidden">
                <div className="w-10 h-10 rounded-xl bg-gradient-to-br from-purple-600 via-purple-700 to-purple-900 flex items-center justify-center shadow-lg shadow-purple-900/30 overflow-hidden">
                  <span className="text-white text-lg font-bold">A</span>
                </div>
                <div>
                  <h1 className="text-lg font-bold bg-gradient-to-r from-purple-900 to-purple-600 bg-clip-text text-transparent">Ateliya</h1>
                  <p className="text-xs text-purple-600">Gestion de couture</p>
                </div>
              </div>
            )}
            {isCollapsed && (
              <div className="w-10 h-10 rounded-xl bg-gradient-to-br from-purple-600 via-purple-700 to-purple-900 flex items-center justify-center shadow-lg shadow-purple-900/30 overflow-hidden mx-auto">
                <span className="text-white text-lg font-bold">A</span>
              </div>
            )}
            {/* Close button for mobile */}
            <button
              onClick={onToggle}
              className="lg:hidden p-2 rounded-lg hover:bg-purple-100 transition-colors"
            >
              <svg className="w-5 h-5 text-purple-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path strokeLinecap="round" strokeLinejoin="round" strokeWidth={2} d="M6 18L18 6M6 6l12 12" />
              </svg>
            </button>
          </div>
        </div>

        {/* Collapse Toggle Button - Desktop only */}
        <div className="hidden lg:block absolute -right-3 top-20 z-10">
          <button
            onClick={toggleCollapse}
            className="bg-white hover:bg-purple-50 text-purple-600 hover:text-purple-900 p-1.5 rounded-full shadow-lg shadow-purple-900/20 border-2 border-purple-200 transition-all duration-200 hover:scale-110 hover:border-purple-600"
            title={isCollapsed ? "Étendre le menu" : "Réduire le menu"}
          >
            {isCollapsed ? (
              <ChevronRight className="w-4 h-4" />
            ) : (
              <ChevronLeft className="w-4 h-4" />
            )}
          </button>
        </div>

        {/* Navigation */}
        <nav className={`p-3 space-y-1 overflow-y-auto h-[calc(100vh-180px)] ${isCollapsed ? 'overflow-x-hidden' : ''}`}>
          {filteredMenuItems.map((item) => (
            <div key={item.title}>
              {item.path ? (
                <Link
                  href={item.path}
                  onClick={() => {
                    if (window.innerWidth < 1024) {
                      onToggle();
                    }
                  }}
                  className={`
                    flex items-center space-x-3 px-3 py-2.5 rounded-xl transition-all duration-200 group relative
                    ${isActive(item.path)
                      ? 'bg-gradient-to-r from-purple-600 to-purple-700 text-white shadow-lg shadow-purple-600/30'
                      : 'text-purple-900 hover:bg-purple-100 hover:text-purple-700'
                    }
                    ${isCollapsed ? 'justify-center' : ''}
                  `}
                  title={isCollapsed ? item.title : ''}
                >
                  <div className={`${isActive(item.path) ? 'text-white' : 'text-purple-600 group-hover:text-purple-700'} flex-shrink-0`}>
                    {item.icon}
                  </div>
                  {!isCollapsed && (
                    <>
                      <span className="font-medium text-sm">{item.title}</span>
                      {isActive(item.path) && (
                        <div className="ml-auto w-1.5 h-1.5 bg-white rounded-full shadow-lg"></div>
                      )}
                    </>
                  )}
                  {isCollapsed && isActive(item.path) && (
                    <div className="absolute right-1 w-1 h-6 bg-purple-600 rounded-full shadow-lg"></div>
                  )}
                </Link>
              ) : (
                <>
                  <button
                    onClick={() => toggleMenu(item.title)}
                    className={`
                      w-full flex items-center justify-between px-3 py-2.5 rounded-xl transition-all duration-200 group relative
                      ${isParentActive(item)
                        ? 'bg-gradient-to-r from-purple-600 to-purple-700 text-white shadow-purple-600/30'
                        : openMenus.includes(item.title)
                          ? 'bg-purple-100 text-purple-900'
                          : 'text-purple-900 hover:bg-purple-100 hover:text-purple-700'
                      }
                      ${isCollapsed ? 'justify-center' : ''}
                    `}
                    title={isCollapsed ? item.title : ''}
                  >
                    <div className={`flex items-center space-x-3 ${isCollapsed ? '' : 'flex-1'}`}>
                      <div className={`${isParentActive(item) ? 'text-white' : openMenus.includes(item.title) ? 'text-purple-700' : 'text-purple-600 group-hover:text-purple-700'} flex-shrink-0`}>
                        {item.icon}
                      </div>
                      {!isCollapsed && (
                        <span className="font-medium text-sm">{item.title}</span>
                      )}
                    </div>
                    {!isCollapsed && (
                      <svg
                        className={`w-4 h-4 transition-transform duration-200 flex-shrink-0 ${openMenus.includes(item.title) ? 'rotate-180' : ''} ${isParentActive(item) ? 'text-white' : 'text-purple-400'}`}
                        fill="none"
                        stroke="currentColor"
                        viewBox="0 0 24 24"
                      >
                        <path strokeLinecap="round" strokeLinejoin="round" strokeWidth={2} d="M19 9l-7 7-7-7" />
                      </svg>
                    )}
                    {isCollapsed && isParentActive(item) && (
                      <div className="absolute right-1 w-1 h-6 bg-purple-600 rounded-full shadow-lg"></div>
                    )}
                  </button>
                  {!isCollapsed && openMenus.includes(item.title) && item.subItems && (
                    <div className="mt-1 ml-3 pl-3 border-l-2 border-purple-300 space-y-1">
                      {item.subItems.map((subItem) => (
                        <Link
                          key={subItem.path}
                          href={subItem.path}
                          onClick={() => {
                            if (window.innerWidth < 1024) {
                              onToggle();
                            }
                          }}
                          className={`
                            block px-3 py-2 text-sm rounded-lg transition-all duration-200
                            ${isSubItemActive(subItem.path)
                              ? 'bg-gradient-to-r from-purple-300 to-purple-200 text-purple-900 font-semibold shadow-sm'
                              : 'text-purple-800 hover:bg-purple-50 hover:text-purple-900'
                            }
                          `}
                        >
                          {subItem.title}
                        </Link>
                      ))}
                    </div>
                  )}
                </>
              )}
            </div>
          ))}
        </nav>

        {/* Bottom Section */}
        <div className="absolute bottom-0 left-0 right-0 p-3 border-t border-purple-200 bg-gradient-to-r from-white via-purple-50 to-white backdrop-blur-sm">
          {!isCollapsed ? (
            <div className="flex items-center space-x-3 p-2.5 bg-gradient-to-r from-purple-100 to-purple-50 rounded-xl border border-purple-200 hover:shadow-md hover:border-purple-300 transition-all duration-200">
              <div className="w-9 h-9 bg-gradient-to-br from-purple-600 to-purple-900 rounded-full flex items-center justify-center shadow-lg shadow-purple-900/30 flex-shrink-0">
                <span className="text-white text-sm font-semibold">U</span>
              </div>
              <div className="flex-1 min-w-0">
                <p className="text-sm font-semibold text-purple-900 truncate">Utilisateur</p>
                <p className="text-xs text-purple-600 truncate">{getRoleLabel(userRole)}</p>
              </div>
            </div>
          ) : (
            <div className="flex justify-center">
              <div className="w-9 h-9 bg-gradient-to-br from-purple-600 to-purple-900 rounded-full flex items-center justify-center shadow-lg shadow-purple-900/30">
                <span className="text-white text-sm font-semibold">U</span>
              </div>
            </div>
          )}
        </div>
      </div>
    </>
  );
}