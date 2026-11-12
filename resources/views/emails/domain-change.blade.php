{{--
    Domain change notice e-mail.

    E-mail clients only support table layouts and inline styles, so this
    template intentionally doesn't use Tailwind or external CSS.
--}}
<!DOCTYPE html>
<html lang="pt-BR">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="color-scheme" content="dark">
    <title>Mudamos para {{ $newDomain }}</title>
</head>
<body style="margin:0;padding:0;background-color:#171717;font-family:'Segoe UI',Roboto,Helvetica,Arial,sans-serif;color:#ffffff;">
    <div style="display:none;max-height:0;overflow:hidden;opacity:0;">
        {{ $oldDomain }} agora é {{ $newDomain }}. Mesma conta, mesmo saldo — novo endereço.
    </div>

    <table role="presentation" width="100%" cellpadding="0" cellspacing="0" border="0" style="background-color:#171717;">
        <tr>
            <td align="center" style="padding:24px 12px;">
                <table role="presentation" width="600" cellpadding="0" cellspacing="0" border="0" style="width:100%;max-width:600px;background-color:#1c1c1c;border-radius:16px;overflow:hidden;border:1px solid #2a2a2a;">

                    {{-- Brand bar --}}
                    <tr>
                        <td style="background-color:#13593f;background-image:linear-gradient(90deg,#0b3326,#13593f,#0b3326);padding:20px 28px;">
                            <table role="presentation" width="100%" cellpadding="0" cellspacing="0" border="0">
                                <tr>
                                    <td style="font-size:28px;font-weight:800;font-style:italic;letter-spacing:-0.5px;">
                                        <span style="color:#ffffff;">bet</span><span style="color:#f7d417;">368</span>
                                    </td>
                                    <td align="right" style="font-size:12px;font-weight:600;color:#3defb0;">
                                        &#9679; Aviso oficial
                                    </td>
                                </tr>
                            </table>
                        </td>
                    </tr>

                    {{-- Headline --}}
                    <tr>
                        <td align="center" style="padding:40px 28px 8px;">
                            <p style="margin:0 0 12px;font-size:12px;font-weight:700;letter-spacing:3px;text-transform:uppercase;color:#3defb0;">
                                Mudança de domínio
                            </p>
                            <h1 style="margin:0;font-size:32px;line-height:1.15;font-weight:800;color:#ffffff;">
                                Estamos em um <span style="color:#3defb0;">novo endereço</span>
                            </h1>
                            <p style="margin:16px 0 0;font-size:16px;line-height:1.6;color:#b5b5b5;">
                                A partir de agora, tudo o que você ama está em
                                <strong style="color:#ffffff;">{{ $newDomain }}</strong>.
                                Nada muda para você — só o endereço no seu navegador.
                            </p>
                        </td>
                    </tr>

                    {{-- Old domain → new domain --}}
                    <tr>
                        <td style="padding:28px 28px 8px;">
                            <table role="presentation" width="100%" cellpadding="0" cellspacing="0" border="0">
                                <tr>
                                    <td style="background-color:#232323;border:1px solid #333333;border-radius:12px;padding:16px 20px;">
                                        <p style="margin:0;font-size:11px;letter-spacing:1px;text-transform:uppercase;color:#7a7a7a;">Endereço antigo</p>
                                        <p style="margin:4px 0 0;font-family:Consolas,Menlo,monospace;font-size:18px;color:#8a8a8a;text-decoration:line-through;">{{ $oldDomain }}</p>
                                    </td>
                                </tr>
                                <tr>
                                    <td align="center" style="padding:10px 0;font-size:22px;color:#3defb0;">&#8595;</td>
                                </tr>
                                <tr>
                                    <td style="background-color:#0b3326;border:1px solid #3defb0;border-radius:12px;padding:16px 20px;">
                                        <p style="margin:0;font-size:11px;letter-spacing:1px;text-transform:uppercase;color:#3defb0;">&#10004; Novo endereço oficial</p>
                                        <p style="margin:4px 0 0;font-family:Consolas,Menlo,monospace;font-size:18px;font-weight:700;color:#ffffff;">{{ $newDomain }}</p>
                                    </td>
                                </tr>
                            </table>
                        </td>
                    </tr>

                    {{-- Call to action --}}
                    <tr>
                        <td align="center" style="padding:28px 28px 8px;">
                            <a href="{{ $newDomainUrl }}" target="_blank" style="display:inline-block;background-color:#3defb0;color:#0b3326;font-size:16px;font-weight:700;text-decoration:none;padding:14px 32px;border-radius:999px;">
                                Ir para {{ $newDomain }}
                            </a>
                        </td>
                    </tr>

                    {{-- Benefits --}}
                    <tr>
                        <td style="padding:28px 28px 8px;">
                            <table role="presentation" width="100%" cellpadding="0" cellspacing="0" border="0">
                                @foreach ([
                                    ['Mesma conta e saldo', 'Entre com os seus dados de sempre. Seu saldo, bônus e histórico já estão esperando por você.'],
                                    ['Conexão mais segura', 'O novo endereço roda em uma infraestrutura atualizada e totalmente criptografada.'],
                                    ['Atualize seus favoritos', 'Salve o novo endereço agora. O domínio antigo deixará de funcionar em breve.'],
                                ] as [$title, $description])
                                    <tr>
                                        <td style="padding:0 0 12px;">
                                            <table role="presentation" width="100%" cellpadding="0" cellspacing="0" border="0" style="background-color:#232323;border:1px solid #2f2f2f;border-radius:12px;">
                                                <tr>
                                                    <td style="padding:14px 18px;">
                                                        <p style="margin:0;font-size:15px;font-weight:700;color:#ffffff;">
                                                            <span style="color:#3defb0;">&#10003;</span> {{ $title }}
                                                        </p>
                                                        <p style="margin:4px 0 0;font-size:14px;line-height:1.5;color:#9a9a9a;">{{ $description }}</p>
                                                    </td>
                                                </tr>
                                            </table>
                                        </td>
                                    </tr>
                                @endforeach
                            </table>
                        </td>
                    </tr>

                    {{-- Safety warning --}}
                    <tr>
                        <td style="padding:8px 28px 32px;">
                            <table role="presentation" width="100%" cellpadding="0" cellspacing="0" border="0" style="background-color:#2a2610;border:1px solid #5c5320;border-radius:12px;">
                                <tr>
                                    <td style="padding:14px 18px;font-size:14px;line-height:1.5;color:#d6d6d6;">
                                        <strong style="color:#f7d417;">Fique atento:</strong>
                                        nosso único endereço oficial é <strong style="color:#ffffff;">{{ $newDomain }}</strong>.
                                        Nunca pedimos sua senha por e-mail, chat ou redes sociais.
                                    </td>
                                </tr>
                            </table>
                        </td>
                    </tr>

                    {{-- Footer --}}
                    <tr>
                        <td align="center" style="padding:20px 28px;border-top:1px solid #2a2a2a;font-size:12px;color:#6f6f6f;">
                            © {{ now()->year }} bet368 · {{ $oldDomain }} agora é {{ $newDomain }}
                        </td>
                    </tr>
                </table>
            </td>
        </tr>
    </table>

    @if ($trackingPixelUrl)
        <img src="{{ $trackingPixelUrl }}" width="1" height="1" alt="" style="display:block;width:1px;height:1px;border:0;">
    @endif
</body>
</html>
