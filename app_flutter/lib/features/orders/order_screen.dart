import 'package:flutter/material.dart';

import '../../app/theme/app_theme.dart';
import 'order_models.dart';

class OrderScreen extends StatelessWidget {
  const OrderScreen({super.key, required this.onNavigateHome});

  final VoidCallback onNavigateHome;

  @override
  Widget build(BuildContext context) {
    final products = orderDrafts.fold<int>(
      0,
      (sum, group) => sum + group.items.length,
    );
    final total = orderDrafts.fold<double>(
      0,
      (sum, group) => sum + group.subtotal,
    );

    return Scaffold(
      body: Stack(
        children: [
          CustomScrollView(
            slivers: [
              SliverAppBar(
                pinned: true,
                expandedHeight: 150,
                toolbarHeight: 82,
                backgroundColor: AppTheme.sand,
                surfaceTintColor: Colors.transparent,
                flexibleSpace: FlexibleSpaceBar(
                  background: Container(
                    padding: const EdgeInsets.fromLTRB(20, 74, 20, 20),
                    decoration: const BoxDecoration(
                      gradient: LinearGradient(
                        begin: Alignment.topLeft,
                        end: Alignment.bottomRight,
                        colors: [Color(0xFFF0E6D8), Color(0xFFF8F4ED)],
                      ),
                    ),
                    child: Column(
                      crossAxisAlignment: CrossAxisAlignment.start,
                      children: [
                        Text(
                          'Pedidos de piso',
                          style: Theme.of(context).textTheme.headlineSmall,
                        ),
                        const SizedBox(height: 6),
                        Row(
                          children: [
                            Container(
                              padding: const EdgeInsets.symmetric(
                                horizontal: 12,
                                vertical: 8,
                              ),
                              decoration: BoxDecoration(
                                color: AppTheme.forestSoft,
                                borderRadius: BorderRadius.circular(999),
                              ),
                              child: const Row(
                                mainAxisSize: MainAxisSize.min,
                                children: [
                                  Icon(
                                    Icons.store_mall_directory_rounded,
                                    size: 16,
                                    color: AppTheme.forest,
                                  ),
                                  SizedBox(width: 6),
                                  Text(
                                    'Sucursal Centro',
                                    style: TextStyle(
                                      color: AppTheme.forest,
                                      fontWeight: FontWeight.w700,
                                    ),
                                  ),
                                ],
                              ),
                            ),
                            const Spacer(),
                            TextButton.icon(
                              onPressed: onNavigateHome,
                              icon: const Icon(Icons.home_rounded),
                              label: const Text('Home'),
                            ),
                          ],
                        ),
                      ],
                    ),
                  ),
                ),
              ),
              SliverPadding(
                padding: const EdgeInsets.fromLTRB(20, 18, 20, 120),
                sliver: SliverList(
                  delegate: SliverChildListDelegate([
                    const _SegmentHeader(),
                    const SizedBox(height: 18),
                    const _HeroSearch(),
                    const SizedBox(height: 14),
                    _SummaryPill(
                      products: products,
                      warehouses: orderDrafts.length,
                      total: total,
                    ),
                    const SizedBox(height: 18),
                    ...orderDrafts.map(
                      (draft) => Padding(
                        padding: const EdgeInsets.only(bottom: 14),
                        child: _WarehouseAccordion(draft: draft),
                      ),
                    ),
                    const SizedBox(height: 6),
                    const _NotesCard(),
                  ]),
                ),
              ),
            ],
          ),
          Positioned(
            left: 16,
            right: 16,
            bottom: 16,
            child: SafeArea(
              top: false,
              child: Container(
                padding: const EdgeInsets.all(10),
                decoration: BoxDecoration(
                  color: Colors.white.withValues(alpha: 0.96),
                  borderRadius: BorderRadius.circular(24),
                  border: Border.all(color: AppTheme.stroke),
                  boxShadow: const [
                    BoxShadow(
                      color: Color(0x14000000),
                      blurRadius: 20,
                      offset: Offset(0, 8),
                    ),
                  ],
                ),
                child: ElevatedButton.icon(
                  onPressed: () {},
                  icon: const Icon(Icons.save_alt_rounded),
                  label: Text(
                    'Generar ${orderDrafts.length} pedidos  •  ${_money(total)}',
                  ),
                ),
              ),
            ),
          ),
        ],
      ),
    );
  }
}

class _SegmentHeader extends StatelessWidget {
  const _SegmentHeader();

  @override
  Widget build(BuildContext context) {
    return Row(
      children: [
        Expanded(
          child: Container(
            padding: const EdgeInsets.all(4),
            decoration: BoxDecoration(
              color: Colors.white,
              borderRadius: BorderRadius.circular(18),
              border: Border.all(color: AppTheme.stroke),
            ),
            child: Row(
              children: [
                Expanded(
                  child: Container(
                    padding: const EdgeInsets.symmetric(vertical: 12),
                    decoration: BoxDecoration(
                      color: AppTheme.paper,
                      borderRadius: BorderRadius.circular(14),
                    ),
                    child: const Row(
                      mainAxisAlignment: MainAxisAlignment.center,
                      children: [
                        Icon(Icons.playlist_add_rounded, size: 18),
                        SizedBox(width: 8),
                        Text(
                          'Nuevo pedido',
                          style: TextStyle(fontWeight: FontWeight.w700),
                        ),
                      ],
                    ),
                  ),
                ),
                const SizedBox(width: 8),
                Expanded(
                  child: Container(
                    padding: const EdgeInsets.symmetric(vertical: 12),
                    child: const Row(
                      mainAxisAlignment: MainAxisAlignment.center,
                      children: [
                        Icon(
                          Icons.list_alt_rounded,
                          size: 18,
                          color: AppTheme.muted,
                        ),
                        SizedBox(width: 8),
                        Text(
                          'Pedidos del día',
                          style: TextStyle(
                            color: AppTheme.muted,
                            fontWeight: FontWeight.w600,
                          ),
                        ),
                      ],
                    ),
                  ),
                ),
              ],
            ),
          ),
        ),
      ],
    );
  }
}

class _HeroSearch extends StatelessWidget {
  const _HeroSearch();

  @override
  Widget build(BuildContext context) {
    return Column(
      children: [
        TextField(
          decoration: InputDecoration(
            hintText: 'Escanea o busca un producto…',
            prefixIcon: const Icon(Icons.qr_code_scanner_rounded),
            suffixIcon: Container(
              margin: const EdgeInsets.all(8),
              decoration: BoxDecoration(
                color: AppTheme.forestSoft,
                borderRadius: BorderRadius.circular(14),
              ),
              child: const Icon(Icons.search_rounded, color: AppTheme.forest),
            ),
          ),
        ),
        const SizedBox(height: 12),
        Align(
          alignment: Alignment.centerLeft,
          child: Wrap(
            spacing: 8,
            runSpacing: 8,
            children: const [
              Chip(label: Text('Siena Beige 60x60')),
              Chip(label: Text('Adhesivo porcelánico')),
              Chip(label: Text('Boquilla arena')),
            ],
          ),
        ),
      ],
    );
  }
}

class _SummaryPill extends StatelessWidget {
  const _SummaryPill({
    required this.products,
    required this.warehouses,
    required this.total,
  });

  final int products;
  final int warehouses;
  final double total;

  @override
  Widget build(BuildContext context) {
    return Container(
      padding: const EdgeInsets.symmetric(horizontal: 16, vertical: 14),
      decoration: BoxDecoration(
        color: Colors.white,
        borderRadius: BorderRadius.circular(18),
        border: Border.all(color: AppTheme.stroke),
      ),
      child: Row(
        children: [
          Text(
            '$products productos',
            style: Theme.of(
              context,
            ).textTheme.bodyMedium?.copyWith(fontWeight: FontWeight.w700),
          ),
          const Padding(
            padding: EdgeInsets.symmetric(horizontal: 10),
            child: Icon(Icons.circle, size: 4, color: AppTheme.muted),
          ),
          Text(
            '$warehouses almacenes',
            style: Theme.of(
              context,
            ).textTheme.bodyMedium?.copyWith(color: AppTheme.muted),
          ),
          const Spacer(),
          Text(_money(total), style: Theme.of(context).textTheme.titleMedium),
        ],
      ),
    );
  }
}

class _WarehouseAccordion extends StatelessWidget {
  const _WarehouseAccordion({required this.draft});

  final OrderDraft draft;

  @override
  Widget build(BuildContext context) {
    return Card(
      child: ExpansionTile(
        shape: RoundedRectangleBorder(borderRadius: BorderRadius.circular(24)),
        collapsedShape: RoundedRectangleBorder(
          borderRadius: BorderRadius.circular(24),
        ),
        tilePadding: const EdgeInsets.symmetric(horizontal: 18, vertical: 8),
        childrenPadding: const EdgeInsets.fromLTRB(18, 0, 18, 18),
        title: Text(
          draft.warehouse,
          style: Theme.of(context).textTheme.titleMedium,
        ),
        subtitle: Padding(
          padding: const EdgeInsets.only(top: 6),
          child: Text(
            '${draft.items.length} producto(s) • ${draft.totalUnits} pieza(s)',
            style: Theme.of(context).textTheme.bodySmall,
          ),
        ),
        trailing: Text(
          _money(draft.subtotal),
          style: Theme.of(context).textTheme.titleMedium,
        ),
        children: [
          const Divider(height: 16),
          ...draft.items.map((item) => _ProductRow(item: item)),
          const SizedBox(height: 12),
          Row(
            children: [
              Expanded(
                child: OutlinedButton(
                  onPressed: () {},
                  child: const Text('Vaciar'),
                ),
              ),
              const SizedBox(width: 12),
              Expanded(
                child: ElevatedButton(
                  onPressed: () {},
                  child: const Text('Generar pedido'),
                ),
              ),
            ],
          ),
        ],
      ),
    );
  }
}

class _ProductRow extends StatelessWidget {
  const _ProductRow({required this.item});

  final OrderItem item;

  @override
  Widget build(BuildContext context) {
    return Padding(
      padding: const EdgeInsets.only(bottom: 14),
      child: Row(
        crossAxisAlignment: CrossAxisAlignment.start,
        children: [
          Expanded(
            child: Column(
              crossAxisAlignment: CrossAxisAlignment.start,
              children: [
                Text(
                  item.name,
                  style: Theme.of(
                    context,
                  ).textTheme.bodyMedium?.copyWith(fontWeight: FontWeight.w700),
                ),
                const SizedBox(height: 4),
                Text(item.sku, style: Theme.of(context).textTheme.bodySmall),
                const SizedBox(height: 4),
                Text(
                  'Capturó ${item.capturedBy}',
                  style: Theme.of(context).textTheme.bodySmall?.copyWith(
                    color: AppTheme.forest,
                    fontWeight: FontWeight.w700,
                  ),
                ),
              ],
            ),
          ),
          const SizedBox(width: 12),
          Column(
            crossAxisAlignment: CrossAxisAlignment.end,
            children: [
              Container(
                padding: const EdgeInsets.symmetric(
                  horizontal: 10,
                  vertical: 8,
                ),
                decoration: BoxDecoration(
                  color: AppTheme.paper,
                  borderRadius: BorderRadius.circular(14),
                  border: Border.all(color: AppTheme.stroke),
                ),
                child: Text(
                  '${item.quantity} pzas',
                  style: Theme.of(
                    context,
                  ).textTheme.bodyMedium?.copyWith(fontWeight: FontWeight.w700),
                ),
              ),
              const SizedBox(height: 8),
              Text(
                _money(item.total),
                style: Theme.of(context).textTheme.bodyMedium?.copyWith(
                  color: AppTheme.muted,
                  fontWeight: FontWeight.w700,
                ),
              ),
            ],
          ),
        ],
      ),
    );
  }
}

class _NotesCard extends StatelessWidget {
  const _NotesCard();

  @override
  Widget build(BuildContext context) {
    return Card(
      child: Padding(
        padding: const EdgeInsets.all(18),
        child: Column(
          crossAxisAlignment: CrossAxisAlignment.start,
          children: [
            Text(
              'Notas del pedido',
              style: Theme.of(context).textTheme.titleMedium,
            ),
            const SizedBox(height: 10),
            Text(
              'Aquí caben instrucciones especiales, referencias del cliente o detalles de entrega.',
              style: Theme.of(context).textTheme.bodySmall?.copyWith(
                color: AppTheme.muted,
                height: 1.45,
              ),
            ),
            const SizedBox(height: 14),
            const TextField(
              minLines: 3,
              maxLines: 5,
              decoration: InputDecoration(
                hintText:
                    'Ejemplo: separar material frágil y confirmar tono antes de surtir.',
              ),
            ),
          ],
        ),
      ),
    );
  }
}

String _money(double value) {
  return '\$${value.toStringAsFixed(2)}';
}
