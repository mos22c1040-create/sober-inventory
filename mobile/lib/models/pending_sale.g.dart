// GENERATED CODE - DO NOT MODIFY BY HAND

part of 'pending_sale.dart';

// **************************************************************************
// TypeAdapterGenerator
// **************************************************************************

class PendingSaleAdapter extends TypeAdapter<PendingSale> {
  @override
  final int typeId = 1;

  @override
  PendingSale read(BinaryReader reader) {
    final numOfFields = reader.readByte();
    final fields = <int, dynamic>{
      for (int i = 0; i < numOfFields; i++) reader.readByte(): reader.read(),
    };
    return PendingSale(
      localId: fields[0] as String,
      items: (fields[1] as List)
          .map((dynamic e) => (e as Map).cast<String, dynamic>())
          .toList(),
      total: fields[2] as double,
      customerName: fields[3] as String,
      paymentMethod: fields[4] as String,
      discount: fields[5] as double,
      notes: fields[6] as String?,
      createdAt: fields[7] as DateTime,
      isSynced: fields[8] as bool,
      errorMessage: fields[9] as String?,
    );
  }

  @override
  void write(BinaryWriter writer, PendingSale obj) {
    writer
      ..writeByte(10)
      ..writeByte(0)
      ..write(obj.localId)
      ..writeByte(1)
      ..write(obj.items)
      ..writeByte(2)
      ..write(obj.total)
      ..writeByte(3)
      ..write(obj.customerName)
      ..writeByte(4)
      ..write(obj.paymentMethod)
      ..writeByte(5)
      ..write(obj.discount)
      ..writeByte(6)
      ..write(obj.notes)
      ..writeByte(7)
      ..write(obj.createdAt)
      ..writeByte(8)
      ..write(obj.isSynced)
      ..writeByte(9)
      ..write(obj.errorMessage);
  }

  @override
  int get hashCode => typeId.hashCode;

  @override
  bool operator ==(Object other) =>
      identical(this, other) ||
      other is PendingSaleAdapter &&
          runtimeType == other.runtimeType &&
          typeId == other.typeId;
}
