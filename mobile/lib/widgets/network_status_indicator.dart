import 'package:flutter/material.dart';
import '../services/sync_service.dart';

class NetworkStatusIndicator extends StatefulWidget {
  final SyncService syncService;
  final VoidCallback? onForceSync;

  const NetworkStatusIndicator({
    super.key,
    required this.syncService,
    this.onForceSync,
  });

  @override
  State<NetworkStatusIndicator> createState() => _NetworkStatusIndicatorState();
}

class _NetworkStatusIndicatorState extends State<NetworkStatusIndicator> {
  @override
  void initState() {
    super.initState();
    widget.syncService.connectivityStream.listen((isOnline) {
      if (mounted) setState(() {});
    });
  }

  @override
  Widget build(BuildContext context) {
    final isOnline = widget.syncService.isOnline;
    final pendingCount = widget.syncService.pendingCount;

    return Row(
      mainAxisSize: MainAxisSize.min,
      children: [
        // Network Status Icon
        GestureDetector(
          onTap: pendingCount > 0 ? widget.onForceSync : null,
          child: Container(
            padding: const EdgeInsets.symmetric(horizontal: 8, vertical: 4),
            decoration: BoxDecoration(
              color: isOnline 
                  ? Colors.green.withValues(alpha: 0.1) 
                  : Colors.orange.withValues(alpha: 0.1),
              borderRadius: BorderRadius.circular(20),
            ),
            child: Row(
              mainAxisSize: MainAxisSize.min,
              children: [
                Icon(
                  isOnline ? Icons.cloud_done : Icons.cloud_off,
                  size: 18,
                  color: isOnline ? Colors.green : Colors.orange,
                ),
                if (pendingCount > 0) ...[
                  const SizedBox(width: 4),
                  Container(
                    padding: const EdgeInsets.symmetric(horizontal: 6, vertical: 2),
                    decoration: BoxDecoration(
                      color: Colors.orange,
                      borderRadius: BorderRadius.circular(10),
                    ),
                    child: Text(
                      '$pendingCount',
                      style: const TextStyle(
                        color: Colors.white,
                        fontSize: 11,
                        fontWeight: FontWeight.bold,
                      ),
                    ),
                  ),
                ],
              ],
            ),
          ),
        ),
        if (pendingCount > 0) ...[
          const SizedBox(width: 8),
          // Force Sync Button
          IconButton(
            icon: const Icon(Icons.sync, size: 20),
            onPressed: widget.onForceSync,
            tooltip: 'مزامنة الآن',
            padding: EdgeInsets.zero,
            constraints: const BoxConstraints(),
          ),
        ],
      ],
    );
  }
}

class OfflineBanner extends StatelessWidget {
  final int pendingCount;
  final VoidCallback? onSync;

  const OfflineBanner({
    super.key,
    required this.pendingCount,
    this.onSync,
  });

  @override
  Widget build(BuildContext context) {
    if (pendingCount == 0) return const SizedBox.shrink();

    return Container(
      width: double.infinity,
      padding: const EdgeInsets.symmetric(horizontal: 16, vertical: 8),
      color: Colors.orange.shade100,
      child: Row(
        children: [
          Icon(Icons.cloud_off, size: 16, color: Colors.orange.shade700),
          const SizedBox(width: 8),
          Expanded(
            child: Text(
              'الوضع غير متصل - $pendingCount عمليات تنتظر المزامنة',
              style: TextStyle(
                color: Colors.orange.shade700,
                fontSize: 12,
              ),
            ),
          ),
          if (onSync != null)
            TextButton(
              onPressed: onSync,
              child: const Text('مزامنة'),
            ),
        ],
      ),
    );
  }
}