"use client";

import React, { useState } from "react";
import {
  Card,
  CardContent,
  CardHeader,
  CardTitle,
} from "@/components/ui/card";
import { ClientOnly } from '@/components/ui/client-only';
import { Badge } from "@/components/ui/badge";
import { Button } from "@/components/ui/button";
import { Label } from "@/components/ui/label";
import {
  Select,
  SelectContent,
  SelectItem,
  SelectTrigger,
  SelectValue,
} from "@/components/ui/select";
import {
  Table,
  TableBody,
  TableCell,
  TableHead,
  TableHeader,
  TableRow,
} from "@/components/ui/table";
import { DatePickerWithRange } from "@/components/ui/date-picker-with-range";
import { addDays } from "date-fns";
import { DateRange } from "react-day-picker";
import {
  ArrowUpRight,
  Calendar,
  CheckCircle2,
  Clock,
  FileText,
  LineChart,
  Receipt,
  TrendingUp,
  Wallet2,
  TrendingDown,
  Users,
  Scissors,
  ShoppingBag,
} from "lucide-react";

import {
  ResponsiveContainer,
  AreaChart,
  Area,
  CartesianGrid,
  XAxis,
  YAxis,
  Tooltip as ReTooltip,
  Legend,
  BarChart,
  Bar,
} from "recharts";

// ------------------
// Helpers & Données Ateliya
// ------------------
const FCFA = (n: number) => {
  if (n >= 1_000_000_000) {
    return (n / 1_000_000_000).toFixed(1) + "B FCFA";
  } else if (n >= 1_000_000) {
    return (n / 1_000_000).toFixed(1) + "M FCFA";
  } else if (n >= 1_000) {
    return (n / 1_000).toFixed(1) + "K FCFA";
  } else {
    return n.toFixed(0) + " FCFA";
  }
};

const kpi = {
  chiffreAffaires: 2_850_000,
  reservationsActives: 24,
  clientsActifs: 156,
  commandesEnCours: 18,
};

const dailySeries = [
  { jour: "Lun", reservations: 8, ventes: 5, factures: 3, revenus: 285_000 },
  { jour: "Mar", reservations: 12, ventes: 7, factures: 4, revenus: 420_000 },
  { jour: "Mer", reservations: 15, ventes: 9, factures: 6, revenus: 510_000 },
  { jour: "Jeu", reservations: 18, ventes: 11, factures: 5, revenus: 680_000 },
  { jour: "Ven", reservations: 22, ventes: 14, factures: 8, revenus: 890_000 },
  { jour: "Sam", reservations: 19, ventes: 12, factures: 7, revenus: 750_000 },
  { jour: "Dim", reservations: 10, ventes: 6, factures: 3, revenus: 320_000 },
];

const revenusParType = [
  { type: "Réservations", revenus: 1_200_000 },
  { type: "Ventes boutique", revenus: 850_000 },
  { type: "Factures", revenus: 650_000 },
  { type: "Mesures", revenus: 150_000 },
];

const activitesBoutique = [
  { activite: "Réservations", nombre: 24, revenus: 1_200_000, progression: 156 },
  { activite: "Ventes directes", nombre: 18, revenus: 850_000, progression: 89 },
  { activite: "Factures clients", nombre: 12, revenus: 650_000, progression: 67 },
  { activite: "Prises de mesures", nombre: 32, revenus: 150_000, progression: 45 },
];

const dernieresTransactions = [
  { id: "RES-20250130-001", type: "Réservation", client: "Marie Kouassi", montant: 45_000, statut: "confirmée" },
  { id: "VTE-20250130-002", type: "Vente", client: "Jean Diabaté", montant: 25_000, statut: "payée" },
  { id: "FAC-20250130-003", type: "Facture", client: "Awa Traoré", montant: 80_000, statut: "partielle" },
  { id: "RES-20250130-004", type: "Réservation", client: "Koffi Yao", montant: 35_000, statut: "en_attente" },
];

// ------------------
// Composant Principal
// ------------------
export default function AteliyaDashboard() {
  const [dateRange, setDateRange] = React.useState<DateRange | undefined>({
    from: new Date(),
    to: addDays(new Date(), 7),
  });
  const [selectedPeriod, setSelectedPeriod] = useState("mois");

  return (
    <ClientOnly>
      <div className="space-y-6">
        {/* En-tête du Dashboard */}
        <div className="flex items-center justify-between">
          <div>
            <h1 className="text-3xl font-bold bg-gradient-to-r from-[#53B0B7] to-teal-700 bg-clip-text text-transparent">
              Dashboard Ateliya
            </h1>
            <p className="text-teal-600 mt-1">Vue d'ensemble de votre atelier de couture</p>
          </div>
          <div className="hidden md:flex items-center gap-3">
            <Button 
              variant="outline"
              className="gap-2 border-teal-300 text-[#53B0B7] hover:bg-teal-50"
            >
              <FileText className="h-4 w-4" />
              Exporter
            </Button>
          </div>
        </div>

        {/* KPIs principaux */}
        <div className="grid gap-4 md:grid-cols-2 lg:grid-cols-4">
          <KpiCard
            title="Chiffre d'affaires"
            value={FCFA(kpi.chiffreAffaires)}
            subtitle="Ce mois"
            icon={<Wallet2 className="h-5 w-5" />}
            trend="+8.2%"
            trendUp={true}
            gradient="from-[#53B0B7] to-teal-700"
          />
          <KpiCard
            title="Réservations actives"
            value={kpi.reservationsActives.toString()}
            subtitle="En cours"
            icon={<Calendar className="h-5 w-5" />}
            trend="+12%"
            trendUp={true}
            gradient="from-teal-500 to-[#53B0B7]"
          />
          <KpiCard
            title="Clients actifs"
            value={kpi.clientsActifs.toString()}
            subtitle="Ce mois"
            icon={<Users className="h-5 w-5" />}
            trend="+5.4%"
            trendUp={true}
            gradient="from-[#53B0B7] to-teal-600"
          />
          <KpiCard
            title="Commandes en cours"
            value={kpi.commandesEnCours.toString()}
            subtitle="À livrer"
            icon={<Scissors className="h-5 w-5" />}
            trend="Stable"
            trendUp={true}
            gradient="from-emerald-500 to-teal-600"
          />
        </div>

        {/* Graphiques */}
        <div className="grid gap-4 lg:grid-cols-2">
          <Card className="border-teal-200 shadow-lg shadow-teal-900/5">
            <CardHeader className="pb-3 bg-gradient-to-r from-teal-50 to-transparent border-b border-teal-100">
              <CardTitle className="flex items-center gap-2 text-teal-900">
                <div className="p-2 bg-[#53B0B7] rounded-lg shadow-lg shadow-teal-600/30">
                  <LineChart className="h-4 w-4 text-white" />
                </div>
                Revenus quotidiens (FCFA)
              </CardTitle>
            </CardHeader>
            <CardContent className="pt-2">
              <div className="h-64">
                <ResponsiveContainer width="100%" height="100%">
                  <AreaChart data={dailySeries} margin={{ left: 0, right: 0, top: 10, bottom: 0 }}>
                    <defs>
                      <linearGradient id="revenusGradient" x1="0" y1="0" x2="0" y2="1">
                        <stop offset="5%" stopColor="#53B0B7" stopOpacity={0.8}/>
                        <stop offset="95%" stopColor="#53B0B7" stopOpacity={0.1}/>
                      </linearGradient>
                    </defs>
                    <CartesianGrid strokeDasharray="3 3" stroke="#B2DFDB" />
                    <XAxis dataKey="jour" stroke="#64748B" style={{ fontSize: '12px' }} />
                    <YAxis tickFormatter={(v) => (v/1_000).toFixed(0) + "K"} stroke="#64748B" style={{ fontSize: '12px' }} />
                    <ReTooltip 
                      formatter={(v: any) => FCFA(v)}
                      contentStyle={{ 
                        backgroundColor: 'white', 
                        border: '1px solid #B2DFDB',
                        borderRadius: '12px',
                        boxShadow: '0 10px 40px -10px rgba(83, 176, 183, 0.3)'
                      }}
                    />
                    <Area 
                      type="monotone" 
                      dataKey="revenus" 
                      name="Revenus" 
                      stroke="#53B0B7" 
                      strokeWidth={3}
                      fill="url(#revenusGradient)" 
                    />
                  </AreaChart>
                </ResponsiveContainer>
              </div>
            </CardContent>
          </Card>

          <Card className="border-teal-200 shadow-lg shadow-teal-900/5">
            <CardHeader className="pb-3 bg-gradient-to-r from-teal-50 to-transparent border-b border-teal-100">
              <CardTitle className="flex items-center gap-2 text-teal-900">
                <div className="p-2 bg-[#53B0B7] rounded-lg shadow-lg shadow-teal-600/30">
                  <TrendingUp className="h-4 w-4 text-white" />
                </div>
                Revenus par type
              </CardTitle>
            </CardHeader>
            <CardContent className="pt-2">
              <div className="h-64">
                <ResponsiveContainer width="100%" height="100%">
                  <BarChart data={revenusParType}>
                    <CartesianGrid strokeDasharray="3 3" stroke="#B2DFDB" />
                    <XAxis dataKey="type" stroke="#64748B" style={{ fontSize: '12px' }} />
                    <YAxis tickFormatter={(v) => (v/1_000).toFixed(0) + "K"} stroke="#64748B" style={{ fontSize: '12px' }} />
                    <ReTooltip 
                      formatter={(v: any) => FCFA(v)}
                      contentStyle={{ 
                        backgroundColor: 'white', 
                        border: '1px solid #B2DFDB',
                        borderRadius: '12px',
                        boxShadow: '0 10px 40px -10px rgba(83, 176, 183, 0.3)'
                      }}
                    />
                    <Bar 
                      dataKey="revenus" 
                      name="Revenus" 
                      fill="#53B0B7"
                      radius={[8, 8, 0, 0]}
                    />
                  </BarChart>
                </ResponsiveContainer>
              </div>
            </CardContent>
          </Card>
        </div>

        {/* Activités boutique */}
        <Card className="border-teal-200 shadow-lg shadow-teal-900/5">
          <CardHeader className="pb-3 bg-gradient-to-r from-teal-50 to-transparent border-b border-teal-100">
            <CardTitle className="flex items-center gap-2 text-teal-900">
              <div className="p-2 bg-[#53B0B7] rounded-lg shadow-lg shadow-teal-600/30">
                <ShoppingBag className="h-4 w-4 text-white" />
              </div>
              Activités de l'atelier
            </CardTitle>
          </CardHeader>
          <CardContent className="pt-4">
            <div className="overflow-hidden rounded-xl border border-teal-200">
              <Table>
                <TableHeader>
                  <TableRow className="bg-gradient-to-r from-[#53B0B7] to-teal-700 hover:from-[#53B0B7] hover:to-teal-700">
                    <TableHead className="text-white font-bold">Activité</TableHead>
                    <TableHead className="text-white font-bold">Nombre</TableHead>
                    <TableHead className="text-white font-bold">Revenus</TableHead>
                    <TableHead className="text-white font-bold">Progression</TableHead>
                  </TableRow>
                </TableHeader>
                <TableBody>
                  {activitesBoutique.map((r, index) => (
                    <TableRow 
                      key={r.activite}
                      className={`${index % 2 === 0 ? 'bg-white' : 'bg-teal-50/50'} hover:bg-teal-100 transition-colors`}
                    >
                      <TableCell className="font-semibold text-teal-900">
                        <div className="flex items-center gap-2">
                          <div className={`w-2 h-2 rounded-full ${
                            r.activite === 'Réservations' ? 'bg-[#53B0B7]' :
                            r.activite === 'Ventes directes' ? 'bg-emerald-500' :
                            r.activite === 'Factures clients' ? 'bg-orange-500' :
                            'bg-purple-500'
                          }`}></div>
                          {r.activite}
                        </div>
                      </TableCell>
                      <TableCell className="font-semibold text-teal-800">{r.nombre}</TableCell>
                      <TableCell className="font-semibold text-teal-700">{FCFA(r.revenus)}</TableCell>
                      <TableCell className="text-teal-600">{r.progression}</TableCell>
                    </TableRow>
                  ))}
                </TableBody>
              </Table>
            </div>
          </CardContent>
        </Card>

        {/* Dernières transactions */}
        <Card className="border-teal-200 shadow-lg shadow-teal-900/5">
          <CardHeader className="pb-3 bg-gradient-to-r from-teal-50 to-transparent border-b border-teal-100">
            <CardTitle className="flex items-center gap-2 text-teal-900">
              <div className="p-2 bg-[#53B0B7] rounded-lg shadow-lg shadow-teal-600/30">
                <Clock className="h-4 w-4 text-white" />
              </div>
              Dernières transactions
            </CardTitle>
          </CardHeader>
          <CardContent className="pt-4">
            <div className="overflow-hidden rounded-xl border border-teal-200">
              <Table>
                <TableHeader>
                  <TableRow className="bg-gradient-to-r from-[#53B0B7] to-teal-700 hover:from-[#53B0B7] hover:to-teal-700">
                    <TableHead className="text-white font-bold">Référence</TableHead>
                    <TableHead className="text-white font-bold">Type</TableHead>
                    <TableHead className="text-white font-bold">Client</TableHead>
                    <TableHead className="text-white font-bold">Montant</TableHead>
                    <TableHead className="text-white font-bold">Statut</TableHead>
                  </TableRow>
                </TableHeader>
                <TableBody>
                  {dernieresTransactions.map((t, index) => (
                    <TableRow 
                      key={t.id}
                      className={`${index % 2 === 0 ? 'bg-white' : 'bg-teal-50/50'} hover:bg-teal-100 transition-colors`}
                    >
                      <TableCell className="font-mono text-xs text-teal-700">{t.id}</TableCell>
                      <TableCell className="font-medium text-teal-900">{t.type}</TableCell>
                      <TableCell className="text-teal-800">{t.client}</TableCell>
                      <TableCell className="font-semibold text-teal-800">{FCFA(t.montant)}</TableCell>
                      <TableCell>
                        <Badge 
                          variant={t.statut === "confirmée" || t.statut === "payée" ? "default" : t.statut === "partielle" ? "secondary" : "outline"}
                          className={
                            t.statut === "confirmée" || t.statut === "payée" 
                              ? "bg-gradient-to-r from-emerald-500 to-green-600 text-white shadow-lg" 
                              : t.statut === "partielle"
                              ? "bg-gradient-to-r from-orange-400 to-orange-500 text-white"
                              : "border-teal-300 text-teal-700"
                          }
                        >
                          {t.statut}
                        </Badge>
                      </TableCell>
                    </TableRow>
                  ))}
                </TableBody>
              </Table>
            </div>
          </CardContent>
        </Card>
      </div>
    </ClientOnly>
  );
}

// ------------------
// KPI Card Component
// ------------------
function KpiCard({
  title,
  value,
  subtitle,
  trend,
  trendUp,
  icon,
  gradient,
}: {
  title: string;
  value: string;
  subtitle?: string;
  trend?: string;
  trendUp?: boolean;
  icon?: React.ReactNode;
  gradient?: string;
}) {
  return (
    <Card className="overflow-hidden border-teal-200 shadow-lg shadow-teal-900/5 hover:shadow-xl hover:shadow-teal-900/10 transition-all duration-300">
      <div className={`h-1.5 bg-gradient-to-r ${gradient || 'from-[#53B0B7] to-teal-700'}`}></div>
      <CardHeader className="pb-3">
        <div className="flex items-center justify-between">
          <CardTitle className="text-sm font-semibold text-[#53B0B7]">{title}</CardTitle>
          <div className={`p-2 bg-gradient-to-br ${gradient || 'from-[#53B0B7] to-teal-700'} rounded-lg shadow-lg`}>
            <div className="text-white">
              {icon}
            </div>
          </div>
        </div>
        <div className="flex items-baseline gap-2 pt-2">
          <span className="text-3xl font-bold text-teal-900">{value}</span>
        </div>
      </CardHeader>
      <CardContent className="pt-0">
        <div className="flex items-center justify-between">
          <span className="text-xs text-[#53B0B7]">{subtitle}</span>
          {trend && (
            <span className={`inline-flex items-center gap-1 text-xs font-semibold px-2 py-1 rounded-full ${
              trendUp 
                ? 'bg-emerald-50 text-emerald-700' 
                : 'bg-red-50 text-red-700'
            }`}>
              {trendUp ? (
                <TrendingUp className="h-3 w-3" />
              ) : (
                <TrendingDown className="h-3 w-3" />
              )}
              {trend}
            </span>
          )}
        </div>
      </CardContent>
    </Card>
  );
}