import 'package:hive/hive.dart';

part 'pending_sale.g.dart';

@HiveType(typeId: 1)
class PendingSale extends HiveObject {
  @HiveField(0)
  String localId;

  @HiveField(1)
  List<Map<String, dynamic>> items;

  @HiveField(2)
  double total;

  @HiveField(3)
  String customerName;

  @HiveField(4)
  String paymentMethod;

  @HiveField(5)
  double discount;

  @HiveField(6)
  String? notes;

  @HiveField(7)
  DateTime createdAt;

  @HiveField(8)
  bool isSynced;

  @HiveField(9)
  String? errorMessage;

  PendingSale({
    required this.localId,
    required this.items,
    required this.total,
    required this.customerName,
    required this.paymentMethod,
    this.discount = 0.0,
    this.notes,
    required this.createdAt,
    this.isSynced = false,
    this.errorMessage,
  });

  Map<String, dynamic> toApiPayload() {
    return {
      'items': items,
      'customer_name': customerName,
      'payment_method': paymentMethod,
      'discount': discount,
      'notes': notes ?? '',
    };
  }
}