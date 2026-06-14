class OrderDraft {
  const OrderDraft({required this.warehouse, required this.items});

  final String warehouse;
  final List<OrderItem> items;

  double get subtotal => items.fold(0, (sum, item) => sum + item.total);
  int get totalUnits => items.fold(0, (sum, item) => sum + item.quantity);
}

class OrderItem {
  const OrderItem({
    required this.name,
    required this.sku,
    required this.quantity,
    required this.price,
    required this.capturedBy,
  });

  final String name;
  final String sku;
  final int quantity;
  final double price;
  final String capturedBy;

  double get total => quantity * price;
}

const orderDrafts = [
  OrderDraft(
    warehouse: 'Almacén Centro',
    items: [
      OrderItem(
        name: 'Piso cerámico Siena Beige 60x60',
        sku: 'SKU-PP-00482',
        quantity: 18,
        price: 249.00,
        capturedBy: 'Ana',
      ),
      OrderItem(
        name: 'Boquilla flexible arena',
        sku: 'SKU-BO-00114',
        quantity: 3,
        price: 89.50,
        capturedBy: 'Ana',
      ),
    ],
  ),
  OrderDraft(
    warehouse: 'Almacén Periférico',
    items: [
      OrderItem(
        name: 'Adhesivo porcelánico 20 kg',
        sku: 'SKU-AD-00918',
        quantity: 4,
        price: 214.00,
        capturedBy: 'Luis',
      ),
      OrderItem(
        name: 'Nivelador cruz 2 mm',
        sku: 'SKU-NI-00210',
        quantity: 2,
        price: 57.00,
        capturedBy: 'Luis',
      ),
    ],
  ),
];
