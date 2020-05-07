<?php

namespace NFePHP\NFSeGinfes;

/**
 * Class for comunications with NFSe webserver in Ginfes Standard
 *
 * @category  NFePHP
 * @package   NFePHP\NFSeGinfes
 * @copyright NFePHP Copyright (c) 2020
 * @license   http://www.gnu.org/licenses/lgpl.txt LGPLv3+
 * @license   https://opensource.org/licenses/MIT MIT
 * @license   http://www.gnu.org/licenses/gpl.txt GPLv3+
 * @author    Cleiton Perin <cperin20 at gmail dot com>
 * @link      http://github.com/nfephp-org/sped-nfse-ginfes for the canonical source repository
 */

use NFePHP\Common\Certificate;
use NFePHP\Common\Validator;
use NFePHP\NFSeGinfes\Common\Signer;
use NFePHP\NFSeGinfes\Common\Tools as BaseTools;

class Tools extends BaseTools
{
    const ERRO_EMISSAO = 1;
    const SERVICO_NAO_CONCLUIDO = 2;

    protected $xsdpath;

    public function __construct($config, Certificate $cert)
    {
        parent::__construct($config, $cert);
        $path = realpath(
            __DIR__ . '/../storage/schemes'
        );
        $this->xmlns1 = "http://www.abrasf.org.br/nfse.xsd";
        $this->xmlns2 = "http:/www.abrasf.org.br/nfse.xsd";
        $this->xsdpath = $path. "/nfse.xsd";
    }

    /**
     * Envia LOTE de RPS para emissão de NFSe (ASSINCRONO)
     * @param array $arps Array contendo de 1 a 50 RPS::class
     * @param string $lote Número do lote de envio
     * @return string
     * @throws \Exception
     */
    public function recepcionarLoteRps($arps, $lote,$apenas_validar = false)
    {
        $operation = 'RecepcionarLoteRpsV3';
        $no_of_rps_in_lot = count($arps);
        if ($no_of_rps_in_lot > 50) {
            throw new \Exception('O limite é de 50 RPS por lote enviado.');
        }
        $content = '';
        foreach ($arps as $rps) {
            $rps->config($this->config);
            $content .= $rps->render();
        }
        $contentmsg = "<EnviarLoteRpsEnvio xmlns=\"{$this->xmlns2}\" xmlns:nfse=\"{$this->xmlns1}\" >"
            . "<LoteRps Id=\"$lote\" versao=\"1.00\">"
            . "<NumeroLote>$lote</NumeroLote>"
            . "<Cnpj>" . $this->config->cnpj . "</Cnpj>"
            . "<InscricaoMunicipal>" . $this->config->im . "</InscricaoMunicipal>"
            . "<QuantidadeRps>$no_of_rps_in_lot</QuantidadeRps>"
            . "<ListaRps>"
            . $content
            . "</ListaRps>"
            . "</LoteRps>"
            . "</EnviarLoteRpsEnvio>";

        $content = Signer::sign(
            $this->certificate,
            $contentmsg,
            'LoteRps',
            'Id',
            OPENSSL_ALGO_SHA1,
            [false, false, null, null],
            'EnviarLoteRpsEnvio'
        );
        $content = str_replace(['<?xml version="1.0"?>', '<?xml version="1.0" encoding="UTF-8"?>'], '', $content);
        
        if($apenas_validar)
        {
            return Validator::isValid($content, $this->xsdpath);
        }
        Validator::isValid($content, $this->xsdpath);
        return $this->send($content, $operation);
    }

    /**
     * Consulta Lote RPS (SINCRONO) após envio com recepcionarLoteRps() (ASSINCRONO)
     * complemento do processo de envio assincono.
     * Que deve ser usado quando temos mais de um RPS sendo enviado
     * por vez.
     * @param string $protocolo
     * @return string
     *
     * Código de situação de lote de RPS
     * 1 – Não Recebido
     * 2 – Não Processado
     * 3 – Processado com Erro
     * 4 – Processado com Sucesso
     */
    public function consultarSituacaoLote($protocolo)
    {
        $operation = "ConsultarSituacaoLoteRpsV3";
        $content = "<ConsultarSituacaoLoteRpsEnvio "
            . " xmlns=\"{$this->xmlns2}\" xmlns:nfse=\"{$this->xmlns1}\">"
            . "<Prestador>"
            . "<Cnpj>" . $this->config->cnpj . "</Cnpj>"
            . "<InscricaoMunicipal>" . $this->config->im . "</InscricaoMunicipal>"
            . "</Prestador>"
            . "<Protocolo>$protocolo</Protocolo>"
            . "</ConsultarSituacaoLoteRpsEnvio>";

        //assinatura dos dados
        $content = Signer::sign(
            $this->certificate,
            $content,
            'ConsultarSituacaoLoteRpsEnvio',
            '',
            OPENSSL_ALGO_SHA1,
            [false, false, null, null]
        );
        $content = str_replace(['<?xml version="1.0"?>', '<?xml version="1.0" encoding="UTF-8"?>'], '', $content);
        Validator::isValid($content, $this->xsdpath);
        return $this->send($content, $operation);
    }

    /**
     * Consulta Lote RPS (SINCRONO) após envio com recepcionarLoteRps() (ASSINCRONO)
     * complemento do processo de envio assincono.
     * Que deve ser usado quando temos mais de um RPS sendo enviado
     * por vez.
     * @param string $protocolo
     * @return string
     */
    public function consultarLoteRps($protocolo)
    {
        $operation = "ConsultarLoteRpsV3";
        $content = "<ConsultarLoteRpsEnvio "
            . " xmlns=\"{$this->xmlns2}\" xmlns:nfse=\"{$this->xmlns1}\" >"
            . "<Prestador>"
            . "<Cnpj>" . $this->config->cnpj . "</Cnpj>"
            . "<InscricaoMunicipal>" . $this->config->im . "</InscricaoMunicipal>"
            . "</Prestador>"
            . "<Protocolo>$protocolo</Protocolo>"
            . "</ConsultarLoteRpsEnvio>";

        //assinatura dos dados
/*        $content = Signer::sign(
            $this->certificate,
            $content,
            'ConsultarLoteRpsEnvio',
            '',
            OPENSSL_ALGO_SHA1,
            [false, false, null, null]
        );*/
        $content = str_replace(['<?xml version="1.0"?>', '<?xml version="1.0" encoding="UTF-8"?>'], '', $content);
        Validator::isValid($content, $this->xsdpath);
        return $this->send($content, $operation);
    }

    /**
     * Consulta NFSe emitidas em um periodo e por tomador (SINCRONO)
     * @param string $dini
     * @param string $dfim
     * @param string $tomadorCnpj
     * @param string $tomadorCpf
     * @param string $tomadorIM
     * @return string
     */
    public function consultarNfse($dini, $dfim, $tomadorCnpj = null, $tomadorCpf = null, $tomadorIM = null)
    {
        $operation = 'ConsultarNfseV3';
        $content = "<ConsultarNfseEnvio xmlns=\"{$this->xmlns2}\" xmlns:nfse=\"{$this->xmlns1}\" >"
            . "<Prestador>"
            . "<Cnpj>" . $this->config->cnpj . "</Cnpj>"
            . "<InscricaoMunicipal>" . $this->config->im . "</InscricaoMunicipal>"
            . "</Prestador>"
            . "<PeriodoEmissao>"
            . "<DataInicial>$dini</DataInicial>"
            . "<DataFinal>$dfim</DataFinal>"
            . "</PeriodoEmissao>";

        if ($tomadorCnpj || $tomadorCpf) {
            $content .= "<Tomador>"
                . "<CpfCnpj>";
            if (isset($tomadorCnpj)) {
                $content .= "<Cnpj>$tomadorCnpj</Cnpj>";
            } else {
                $content .= "<Cpf>$tomadorCpf</Cpf>";
            }
            $content .= "</CpfCnpj>";
            if (isset($tomadorIM)) {
                $content .= "<InscricaoMunicipal>$tomadorIM</InscricaoMunicipal>";
            }
            $content .= "</Tomador>";
        }
        $content .= "</ConsultarNfseEnvio>";
        //assinatura dos dados
/*        $content = Signer::sign(
            $this->certificate,
            $content,
            'ConsultarNfseEnvio',
            '',
            OPENSSL_ALGO_SHA1,
            [false, false, null, null]
        );*/
        $content = str_replace(['<?xml version="1.0"?>', '<?xml version="1.0" encoding="UTF-8"?>'], '', $content);
        Validator::isValid($content, $this->xsdpath);
        return $this->send($content, $operation);
    }

    /**
     * Consulta NFSe por RPS (SINCRONO)
     * @param integer $numero
     * @param string $serie
     * @param integer $tipo
     * @return string
     */
    public function consultarNfsePorRps($numero, $serie, $tipo)
    {
        $operation = "ConsultarNfsePorRpsV3";
        $content = "<ConsultarNfseRpsEnvio xmlns=\"{$this->xmlns2}\" xmlns:nfse=\"{$this->xmlns1}\">"
            . "<IdentificacaoRps>"
            . "<Numero>$numero</Numero>"
            . "<Serie>$serie</Serie>"
            . "<Tipo>$tipo</Tipo>"
            . "</IdentificacaoRps>"
            . "<Prestador>"
            . "<Cnpj>" . $this->config->cnpj . "</Cnpj>"
            . "<InscricaoMunicipal>" . $this->config->im . "</InscricaoMunicipal>"
            . "</Prestador>"
            . "</ConsultarNfseRpsEnvio>";
        //assinatura dos dados
        $content = Signer::sign(
            $this->certificate,
            $content,
            'ConsultarNfseRpsEnvio',
            '',
            OPENSSL_ALGO_SHA1,
            [false, false, null, null]
        );
        $content = str_replace(['<?xml version="1.0"?>', '<?xml version="1.0" encoding="UTF-8"?>'], '', $content);
        Validator::isValid($content, $this->xsdpath);
        return $this->send($content, $operation);
    }

    /**
     * Solicita o cancelamento de NFSe (SINCRONO)
     * @param integer $numero
     * @param integer $codigo
     * @param string $id
     * @param string $versao
     * @return string
     */
    public function cancelarNfse($numero, $codigo = self::ERRO_EMISSAO, $id = null, $versao = "2")
    {
       // if ($versao == "3") {
        return $this->cancelarNfseV3($numero, $codigo, $id);
       // }
       // return $this->cancelarNfseV2($numero);
    }

    /**
     * Solicita o cancelamento de NFSe (SINCRONO)
     * @param integer $numero
     * @param integer $codigo
     * @param string $id
     * @return string
     */
    public function cancelarNfseV3($numero, $codigo = self::ERRO_EMISSAO, $id = null)
    {
        /*
         * Versão 3.0 não funciona em Guarulhos
         */
        if (empty($id)) {
            $id = $numero;
        }
        $operation = 'CancelarNfseV3';
        $xml = "<CancelarNfseEnvio xmlns=\"{$this->xmlns2}\" xmlns:nfse=\"{$this->xmlns1}\">"
            . "<Pedido>"
            . "<InfPedidoCancelamento Id=\"$id\">"
            . "<IdentificacaoNfse>"
            . "<Numero>$numero</Numero>"
            . "<Cnpj>" . $this->config->cnpj . "</Cnpj>"
            . "<InscricaoMunicipal>" . $this->config->im . "</InscricaoMunicipal>"
            . "<CodigoMunicipio>" . $this->config->cmun . "</CodigoMunicipio>"
            . "</IdentificacaoNfse>"
            . "<CodigoCancelamento>$codigo</CodigoCancelamento>"
            . "</InfPedidoCancelamento>"
            . "</Pedido>"
            . "</CancelarNfseEnvio>";

        $content = Signer::sign(
            $this->certificate,
            $xml,
            'InfPedidoCancelamento',
            'Id',
            OPENSSL_ALGO_SHA1,
            [false, false, null, null],
            'Pedido'
        );
        $content = Signer::sign(
            $this->certificate,
            $content,
            'Pedido',
            '',
            OPENSSL_ALGO_SHA1,
            [false, false, null, null],
            'CancelarNfseEnvio'
        );
        $content = str_replace(['<?xml version="1.0"?>', '<?xml version="1.0" encoding="UTF-8"?>'], '', $content);
        Validator::isValid($xml, $this->xsdpath);
        $response = $this->send($content, $operation);
        return $response;
    }

    /**
     * Solicita o cancelamento de NFSe (SINCRONO)
     * @param integer $numero
     * @param integer $codigo
     * @param string $id
     * @return string
     */
    public function cancelarNfseV2($numero)
    {
        /*
         * Versão 2.0 funciona em Guarulhos
         */
        $operation = 'CancelarNfse';
        $xml = "<CancelarNfseEnvio xmlns=\"{$this->xmlns2}\" xmlns:nfse=\"{$this->xmlns1}\">"
            . "<Prestador>"
            . "<Cnpj>" . $this->config->cnpj . "</Cnpj>"
            . "<InscricaoMunicipal>" . $this->config->im . "</InscricaoMunicipal>"
            . "</Prestador>"
            . "<NumeroNfse>$numero</NumeroNfse>"
            . "</CancelarNfseEnvio>";

        $content = Signer::sign(
            $this->certificate,
            $xml,
            'CancelarNfseEnvio',
            '',
            OPENSSL_ALGO_SHA1,
            [false, false, null, null]
        );
        $content = str_replace(['<?xml version="1.0"?>', '<?xml version="1.0" encoding="UTF-8"?>'], '', $content);
        Validator::isValid($content, $this->xsdpath);
        $this->setVersion("2");
        $response = $this->send($content, $operation);
        return $response;
    }
}
