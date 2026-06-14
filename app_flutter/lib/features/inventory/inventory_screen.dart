import 'package:flutter/material.dart';

import '../../app/theme/app_theme.dart';

class InventoryScreen extends StatelessWidget {
  const InventoryScreen({super.key});

  @override
  Widget build(BuildContext context) {
    return Scaffold(
      appBar: AppBar(title: const Text('Inventario')),
      body: ListView(
        padding: const EdgeInsets.fromLTRB(20, 8, 20, 24),
        children: const [
          _InventoryCard(
            title: 'Existencia por almacén',
            subtitle: 'Consulta rápida para decidir de dónde surtir.',
            icon: Icons.warehouse_rounded,
            accent: Color(0xFFE6F4EA),
          ),
          SizedBox(height: 14),
          _InventoryCard(
            title: 'Productos con baja cobertura',
            subtitle: 'Alertas visuales para piezas críticas.',
            icon: Icons.warning_amber_rounded,
            accent: Color(0xFFFCE9D6),
          ),
          SizedBox(height: 14),
          _InventoryCard(
            title: 'Búsqueda por SKU o código de barras',
            subtitle: 'Buen siguiente paso cuando agregues cámara o escáner.',
            icon: Icons.qr_code_scanner_rounded,
            accent: Color(0xFFE9EEF8),
          ),
        ],
      ),
    );
  }
}

class _InventoryCard extends StatelessWidget {
  const _InventoryCard({
    required this.title,
    required this.subtitle,
    required this.icon,
    required this.accent,
  });

  final String title;
  final String subtitle;
  final IconData icon;
  final Color accent;

  @override
  Widget build(BuildContext context) {
    return Card(
      child: Padding(
        padding: const EdgeInsets.all(18),
        child: Row(
          crossAxisAlignment: CrossAxisAlignment.start,
          children: [
            Container(
              width: 52,
              height: 52,
              decoration: BoxDecoration(
                color: accent,
                borderRadius: BorderRadius.circular(18),
              ),
              child: Icon(icon, color: AppTheme.ink),
            ),
            const SizedBox(width: 14),
            Expanded(
              child: Column(
                crossAxisAlignment: CrossAxisAlignment.start,
                children: [
                  Text(title, style: Theme.of(context).textTheme.titleMedium),
                  const SizedBox(height: 6),
                  Text(
                    subtitle,
                    style: Theme.of(context).textTheme.bodySmall?.copyWith(
                      color: AppTheme.muted,
                      height: 1.5,
                    ),
                  ),
                ],
              ),
            ),
          ],
        ),
      ),
    );
  }
}
