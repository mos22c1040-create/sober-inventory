// Patch plugins in pub cache for AGP 8+ (run before projects load)
val pubCache = System.getenv("PUB_CACHE")
    ?: (System.getenv("LOCALAPPDATA")?.let { "$it/Pub/Cache" } ?: (System.getProperty("user.home")!! + "/.pub-cache"))
val pubCacheDir = java.io.File(pubCache)
// blue_thermal_printer: add namespace for AGP 8+
val blueThermal = java.io.File(pubCacheDir, "hosted/pub.dev/blue_thermal_printer-1.2.3/android/build.gradle")
if (blueThermal.exists()) {
    val t = blueThermal.readText()
    if (!t.contains("namespace")) blueThermal.writeText(t.replace("android {", "android {\n    namespace 'id.kakzaki.blue_thermal_printer'"))
}

pluginManagement {
    val flutterSdkPath =
        run {
            val properties = java.util.Properties()
            file("local.properties").inputStream().use { properties.load(it) }
            val flutterSdkPath = properties.getProperty("flutter.sdk")
            require(flutterSdkPath != null) { "flutter.sdk not set in local.properties" }
            flutterSdkPath
        }

    includeBuild("$flutterSdkPath/packages/flutter_tools/gradle")

    repositories {
        google()
        mavenCentral()
        gradlePluginPortal()
    }
}

plugins {
    id("dev.flutter.flutter-plugin-loader") version "1.0.0"
    id("com.android.application") version "8.11.1" apply false
    id("org.jetbrains.kotlin.android") version "2.2.20" apply false
}

include(":app")
