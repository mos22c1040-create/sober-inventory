class Product {
  final int id;
  final String name;
  final String? sku;
  final double price;
  final int quantity;

  Product({
    required this.id,
    required this.name,
    this.sku,
    required this.price,
    required this.quantity,
  });

  factory Product.fromJson(Map<String, dynamic> json) {
    return Product(
      id: (json['id'] ?? 0) as int,
      name: (json['name'] ?? '') as String,
      sku: json['sku'] as String?,
      price: double.tryParse(json['price'].toString()) ?? 0,
      quantity: (json['quantity'] ?? 0) as int,
    );
  }
}

