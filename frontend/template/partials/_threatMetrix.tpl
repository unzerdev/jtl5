{if $hpPayment.threatMetrixId}
    <script type="text/javascript" async
         src="https://h.online-metrix.net/fp/tags.js?org_id=363t8kgq&session_id={$hpPayment.threatMetrixId}">
    </script>

    <noscript>
        <iframe
            style="width: 100px; height: 100px; border: 0; position: absolute; top: -5000px;"
            src="https://h.online-metrix.net/fp/tags?org_id=363t8kgq&session_id={$hpPayment.threatMetrixId}">
        </iframe>
    </noscript>
{/if}