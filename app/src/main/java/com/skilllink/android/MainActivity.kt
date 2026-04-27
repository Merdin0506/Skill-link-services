package com.skilllink.android

import android.annotation.SuppressLint
import android.os.Bundle
import android.webkit.WebChromeClient 
import android.webkit.WebSettings
import android.webkit.WebView
import android.webkit.WebViewClient
import androidx.activity.ComponentActivity
import androidx.activity.compose.setContent
import androidx.compose.foundation.layout.fillMaxSize
import androidx.compose.material3.Surface
import androidx.compose.runtime.Composable
import androidx.compose.ui.Modifier
import androidx.compose.ui.viewinterop.AndroidView

class MainActivity : ComponentActivity() {
    override fun onCreate(savedInstanceState: Bundle?) {
        super.onCreate(savedInstanceState)

        // Enable debugging for WebView
        WebView.setWebContentsDebuggingEnabled(true)

        setContent {
            Surface(modifier = Modifier.fillMaxSize()) {
                SkillLinkApp()
            }
        }
    }
}

@SuppressLint("SetJavaScriptEnabled")
@Composable
fun SkillLinkApp() {
    AndroidView(
        modifier = Modifier.fillMaxSize(),
        factory = { context ->
            WebView(context).apply {
                webViewClient = object : WebViewClient() {
                    override fun shouldOverrideUrlLoading(view: WebView?, request: android.webkit.WebResourceRequest?): Boolean {
                        return false // Allow WebView to handle all URL loading
                    }
                }
                webChromeClient = WebChromeClient()

                settings.apply {
                    javaScriptEnabled = true
                    domStorageEnabled = true
                    loadWithOverviewMode = true
                    useWideViewPort = true
                    builtInZoomControls = true
                    displayZoomControls = false
                    cacheMode = WebSettings.LOAD_DEFAULT
                }

                loadUrl("http://192.168.1.10:8080/Skill-link-services/Backend/public/index.php/auth/login")
            }
        }
    )
}